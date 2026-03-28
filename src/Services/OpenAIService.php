<?php
declare(strict_types=1);

namespace DTZ\Services;

class OpenAIService {
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    
    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Analyze a German writing submission for DTZ B1 level
     * 
     * @param string $text The user's writing
     * @param string $taskType Type of task (bewerbung, beschwerde, etc.)
     * @return array Analysis results
     */
    public function analyzeWriting(string $text, string $taskType): array {
        $prompt = $this->buildPrompt($text, $taskType);
        
        $response = $this->callAPI($prompt);
        
        if (!$response) {
            throw new \Exception('OpenAI API error');
        }
        
        return $this->parseResponse($response);
    }
    
    private function buildPrompt(string $text, string $taskType): array {
        $taskDescriptions = [
            'bewerbung' => 'eine Bewerbung',
            'beschwerde' => 'einen Beschwerdebrief',
            'einladung' => 'eine Einladung',
            'anfrage' => 'eine Anfrage',
            'termin' => 'eine Terminvereinbarung',
            'danksagung' => 'eine Danksagung'
        ];
        
        $taskDesc = $taskDescriptions[$taskType] ?? 'einen Text';
        
        $systemPrompt = "Du bist ein erfahrener DTZ-Prüfer (Deutsch-Test für Zuwanderer) auf B1-Niveau. 
Deine Aufgabe ist es, Schreibproben von Deutschlernenden zu analysieren und konstruktives Feedback zu geben.

WICHTIGE REGELN:
1. Bewahre das A2-B1 Niveau des Lernenden - korrigiere NICHT zu nativem Deutsch
2. Markiere nur echte Fehler, übertreibe nicht
3. Erkenne und lobe gute Formulierungen
4. Sei ermutigend aber ehrlich

BEWERTUNGSKRITERIEN (B1-Niveau):
- Aufgabenerfüllung (0-5 Punkte): Sind alle gefragten Punkte enthalten?
- Textaufbau (0-5 Punkte): Einleitung, Hauptteil, Schluss logisch verbunden?
- Sprachrichtigkeit (0-5 Punkte): Grammatik, Rechtschreibung, Zeichensetzung
- Sprachumfang (0-5 Punkte): Wortschatz, Satzvielfalt

Gib deine Antwort im JSON-Format:
{
    \"overallScore\": 15,
    \"levelAssessment\": \"B1\",
    \"generalFeedback\": \"Übersichtliches Gesamtfeedback...\",
    \"categories\": {
        \"aufgabenerfuellung\": {\"score\": 4, \"feedback\": \"...\"},
        \"textaufbau\": {\"score\": 4, \"feedback\": \"...\"},
        \"sprachrichtigkeit\": {\"score\": 3, \"feedback\": \"...\"},
        \"sprachumfang\": {\"score\": 4, \"feedback\": \"...\"}
    },
    \"corrections\": [
        {
            \"type\": \"grammar\",
            \"severity\": \"major\",
            \"original\": \"Ich habe gehen\",
            \"corrected\": \"Ich bin gegangen\",
            \"explanation\": \"Perfekt mit 'sein' bei Bewegungsverben\",
            \"position\": 45
        }
    ],
    \"highlights\": [
        {
            \"type\": \"good\",
            \"text\": \"Sehr geehrte Damen und Herren\",
            \"comment\": \"Formelle Anrede perfekt!\"
        }
    ]
}";

        $userPrompt = "Bitte analysiere folgende {$taskDesc} auf B1-Niveau:\n\n\"\"\"{$text}\"\"\"";
        
        return [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000,
            'response_format' => ['type' => 'json_object']
        ];
    }
    
    private function callAPI(array $payload): ?array {
        $ch = curl_init("{$this->baseUrl}/chat/completions");
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('OpenAI API curl error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('OpenAI API error: HTTP ' . $httpCode . ' - ' . $response);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    private function parseResponse(array $response): array {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI response structure');
        }
        
        $content = $response['choices'][0]['message']['content'];
        $result = json_decode($content, true);
        
        if (!$result) {
            throw new \Exception('Failed to parse OpenAI JSON response');
        }
        
        // Ensure required fields exist
        $defaults = [
            'overallScore' => 10,
            'levelAssessment' => 'A2',
            'generalFeedback' => 'Analyse abgeschlossen.',
            'categories' => [],
            'corrections' => [],
            'highlights' => []
        ];
        
        return array_merge($defaults, $result);
    }
    
    /**
     * Test if API key is valid
     */
    public function testConnection(): bool {
        try {
            $response = $this->callAPI([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => 'Hello']],
                'max_tokens' => 5
            ]);
            
            return $response !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
