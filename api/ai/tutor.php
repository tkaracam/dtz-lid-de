<?php
/**
 * AI Tutor API Endpoint
 * Provides intelligent tutoring and explanations
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';

use DTZ\Database\Database;
use DTZ\Auth\AuthController;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

// Auth check
$auth = new AuthController();
$user = $auth->authenticate();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$aiTutor = new AITutor($user);

try {
    switch ($action) {
        case 'explain_question':
            $response = $aiTutor->explainQuestion($input);
            break;
            
        case 'get_hint':
            $response = $aiTutor->getHint($input);
            break;
            
        case 'analyze_mistake':
            $response = $aiTutor->analyzeMistake($input);
            break;
            
        case 'personalized_tip':
            $response = $aiTutor->getPersonalizedTip($input);
            break;
            
        case 'grammar_explain':
            $response = $aiTutor->explainGrammar($input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ungültige Aktion']);
            exit;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('AI Tutor error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'KI-Fehler: ' . $e->getMessage()]);
}

class AITutor {
    private array $user;
    private Database $db;
    private string $apiKey;
    
    public function __construct(array $user) {
        $this->user = $user;
        $this->db = Database::getInstance();
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
    }
    
    /**
     * Explain a question in detail
     */
    public function explainQuestion(array $input): array {
        $question = $input['question'] ?? '';
        $options = $input['options'] ?? [];
        $module = $input['module'] ?? 'allgemein';
        
        if (empty($question)) {
            return ['error' => 'Frage erforderlich'];
        }
        
        $prompt = $this->buildExplainPrompt($question, $options, $module);
        
        // Check cache first
        $cacheKey = 'explain_' . md5($question);
        $cached = $this->getFromCache($cacheKey);
        if ($cached) {
            return ['success' => true, 'explanation' => $cached, 'cached' => true];
        }
        
        $explanation = $this->callAI($prompt);
        
        if ($explanation) {
            $this->saveToCache($cacheKey, $explanation, 1440); // 24 hours
            $this->logAIInteraction('explain_question', $question);
            
            return [
                'success' => true,
                'explanation' => $explanation,
                'cached' => false
            ];
        }
        
        return ['error' => 'Erklärung konnte nicht generiert werden'];
    }
    
    /**
     * Get a hint for current question
     */
    public function getHint(array $input): array {
        $question = $input['question'] ?? '';
        $attemptCount = $input['attempt_count'] ?? 0;
        
        if (empty($question)) {
            return ['error' => 'Frage erforderlich'];
        }
        
        $hintLevel = min($attemptCount + 1, 3);
        
        $prompts = [
            1 => "Gib einen kleinen Hinweis für diese Frage. Nenne die Antwort NICHT:\n\n$question",
            2 => "Gib einen konkreteren Hinweis. Erkläre die Grammatik oder Logik:\n\n$question",
            3 => "Erkläre fast die Lösung, aber lass den Nutzer noch selbst denken:\n\n$question"
        ];
        
        $hint = $this->callAI($prompts[$hintLevel] ?? $prompts[1]);
        
        if ($hint) {
            $this->logAIInteraction('get_hint', $question);
            
            return [
                'success' => true,
                'hint' => $hint,
                'hint_level' => $hintLevel,
                'attempt_count' => $attemptCount
            ];
        }
        
        return ['error' => 'Hinweis konnte nicht generiert werden'];
    }
    
    /**
     * Analyze why answer was wrong
     */
    public function analyzeMistake(array $input): array {
        $question = $input['question'] ?? '';
        $userAnswer = $input['user_answer'] ?? '';
        $correctAnswer = $input['correct_answer'] ?? '';
        $module = $input['module'] ?? 'general';
        
        if (empty($question) || empty($userAnswer)) {
            return ['error' => 'Frage und Antwort erforderlich'];
        }
        
        $prompt = "Analysiere diesen Fehler für einen Deutschlerner (DTZ-Niveau):

FRAGE: $question
NUTZERANTWORT: $userAnswer
RICHTIGE ANTWORT: $correctAnswer

Gib eine freundliche, konstruktive Analyse:
1. Warum war die Antwort falsch?
2. Was ist der grammatikalische/logische Hintergrund?
3. Ähnliche Fehler vermeiden - konkreter Tipp
4. Eine Übungsaufgabe zum selben Thema (optional)

Antworte auf Deutsch.";
        
        $analysis = $this->callAI($prompt);
        
        if ($analysis) {
            $this->saveMistake($question, $userAnswer, $correctAnswer, $module);
            $this->logAIInteraction('analyze_mistake', $question);
            
            return [
                'success' => true,
                'analysis' => $analysis,
                'mistake_type' => $this->categorizeMistake($analysis)
            ];
        }
        
        return ['error' => 'Analyse konnte nicht generiert werden'];
    }
    
    /**
     * Get personalized learning tip
     */
    public function getPersonalizedTip(array $input): array {
        $module = $input['module'] ?? 'general';
        $weakAreas = $input['weak_areas'] ?? [];
        
        $mistakePattern = $this->getMistakePattern();
        
        $prompt = "Gib einen personalisierten Lerntipp für einen DTZ-Prüfling:

MODUL: $module
HÄUFIGE FEHLERTYPEN: " . implode(', ', $mistakePattern) . "
SCHWACHE BEREICHE: " . implode(', ', $weakAreas) . "

Erstelle:
1. Einen konkreten, umsetzbaren Lerntipp
2. Eine Übungsmethode für diese Woche
3. Motivierende Worte

Antworte auf Deutsch.";
        
        $tip = $this->callAI($prompt);
        
        if ($tip) {
            $this->logAIInteraction('personalized_tip', json_encode($weakAreas));
            
            return [
                'success' => true,
                'tip' => $tip,
                'based_on' => [
                    'mistake_count' => count($mistakePattern),
                    'weak_areas' => $weakAreas
                ]
            ];
        }
        
        return ['error' => 'Tipp konnte nicht generiert werden'];
    }
    
    /**
     * Explain grammar concept
     */
    public function explainGrammar(array $input): array {
        $topic = $input['topic'] ?? '';
        $example = $input['example'] ?? '';
        
        if (empty($topic)) {
            return ['error' => 'Grammatikthema erforderlich'];
        }
        
        $prompt = "Erkläre dieses Grammatikthema für DTZ-Niveau (A2-B1):

THEMA: $topic
BEISPIEL: $example

Strukturiere die Erklärung:
1. Einfache Regel mit Beispielen
2. Häufige Ausnahmen
3. Übungstipp
4. Merksatz (falls sinnvoll)

Antworte auf Deutsch, einfach und verständlich.";
        
        $explanation = $this->callAI($prompt);
        
        if ($explanation) {
            $this->logAIInteraction('grammar_explain', $topic);
            
            return [
                'success' => true,
                'explanation' => $explanation,
                'topic' => $topic
            ];
        }
        
        return ['error' => 'Erklärung konnte nicht generiert werden'];
    }
    
    /**
     * Call AI API (OpenAI)
     */
    private function callAI(string $prompt): ?string {
        if (empty($this->apiKey) || strpos($this->apiKey, 'placeholder') !== false) {
            return $this->getFallbackResponse($prompt);
        }
        
        try {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            
            $data = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Du bist ein freundlicher Deutschlehrer für den DTZ (Deutsch-Test für Zuwanderer). ' .
                                   'Erkläre einfach, geduldig und motivierend. Antworte immer auf Deutsch.'
                    ],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ];
            
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ],
                CURLOPT_TIMEOUT => 15
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                return $result['choices'][0]['message']['content'] ?? null;
            }
            
            error_log('OpenAI API error: ' . $response);
            return $this->getFallbackResponse($prompt);
            
        } catch (Exception $e) {
            error_log('AI call error: ' . $e->getMessage());
            return $this->getFallbackResponse($prompt);
        }
    }
    
    /**
     * Get fallback response when AI is unavailable
     */
    private function getFallbackResponse(string $prompt): string {
        if (strpos($prompt, 'Hinweis') !== false) {
            return "💡 **Hinweis**: Lesen Sie den Text noch einmal aufmerksam. Achten Sie auf Schlüsselwörter und Zeitformen.";
        }
        
        if (strpos($prompt, 'Fehler') !== false || strpos($prompt, 'Analyse') !== false) {
            return "📚 **Analyse**: Das war ein kleiner Fehler, aber Sie lernen daraus! Achten Sie beim nächsten Mal auf die genaue Formulierung im Text.";
        }
        
        if (strpos($prompt, 'Grammatik') !== false) {
            return "📖 **Grammatik-Erklärung**: Üben Sie diese Struktur mit weiteren Beispielen. Wiederholung ist der Schlüssel zum Erfolg!";
        }
        
        return "🎯 **Erklärung**: Gute Frage! Überlegen Sie: Was ist die Hauptinformation im Text? Welche Details sind nur zusätzlich?";
    }
    
    /**
     * Build explanation prompt
     */
    private function buildExplainPrompt(string $question, array $options, string $module): string {
        $optionsText = '';
        if (!empty($options)) {
            $optionsText = "\n\nOptionen:\n";
            foreach ($options as $key => $value) {
                $optionsText .= "$key) $value\n";
            }
        }
        
        return "Erkläre diese DTZ-Frage detailliert:

MODUL: $module
FRAGE: $question$optionsText

Strukturiere die Erklärung:
1. Was wird gefragt?
2. Welche Strategie hilft hier?
3. Schritt-für-Schritt Lösungsweg
4. Wichtige Vokabeln oder Grammatik

Antworte auf Deutsch.";
    }
    
    /**
     * Get from cache
     */
    private function getFromCache(string $key): ?string {
        try {
            $result = $this->db->selectOne(
                "SELECT response FROM ai_cache WHERE cache_key = ? AND expires_at > datetime('now')",
                [$key]
            );
            return $result['response'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Save to cache
     */
    private function saveToCache(string $key, string $response, int $ttlMinutes): void {
        try {
            $this->db->query(
                "INSERT OR REPLACE INTO ai_cache (cache_key, response, expires_at) 
                 VALUES (?, ?, datetime('now', '+{$ttlMinutes} minutes'))",
                [$key, $response]
            );
        } catch (Exception $e) {
            error_log('Cache save error: ' . $e->getMessage());
        }
    }
    
    /**
     * Log AI interaction
     */
    private function logAIInteraction(string $type, string $input): void {
        try {
            $this->db->query(
                "INSERT INTO ai_interactions (user_id, action_type, input_preview, created_at) 
                 VALUES (?, ?, ?, datetime('now'))",
                [$this->user['id'], $type, substr($input, 0, 200)]
            );
        } catch (Exception $e) {
            error_log('AI log error: ' . $e->getMessage());
        }
    }
    
    /**
     * Save mistake for pattern analysis
     */
    private function saveMistake(string $question, string $userAnswer, string $correctAnswer, string $module): void {
        try {
            $this->db->query(
                "INSERT INTO user_mistakes (user_id, question_preview, user_answer, correct_answer, module, created_at) 
                 VALUES (?, ?, ?, ?, ?, datetime('now'))",
                [$this->user['id'], substr($question, 0, 200), $userAnswer, $correctAnswer, $module]
            );
        } catch (Exception $e) {
            error_log('Mistake save error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get mistake pattern for user
     */
    private function getMistakePattern(): array {
        try {
            $mistakes = $this->db->select(
                "SELECT module, COUNT(*) as count 
                 FROM user_mistakes 
                 WHERE user_id = ? 
                 AND created_at > datetime('now', '-30 days')
                 GROUP BY module
                 ORDER BY count DESC",
                [$this->user['id']]
            );
            
            return array_column($mistakes, 'module');
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Categorize mistake from analysis
     */
    private function categorizeMistake(string $analysis): string {
        $categories = [
            'Vokabular' => ['Wort', 'Bedeutung', 'Vokabel'],
            'Grammatik' => ['Grammatik', 'Zeitform', 'Kasus', 'Deklination'],
            'Leseverstehen' => ['Text', 'lesen', 'verstehen'],
            'Hörverstehen' => ['Hören', 'Audio', 'verstehen'],
            'Aufmerksamkeit' => ['aufmerksam', 'genau', 'Detail']
        ];
        
        $analysisLower = strtolower($analysis);
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($analysisLower, strtolower($keyword)) !== false) {
                    return $category;
                }
            }
        }
        
        return 'Allgemein';
    }
}
