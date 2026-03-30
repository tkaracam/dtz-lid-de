<?php
declare(strict_types=1);

/**
 * Speaking Analysis Endpoint
 * Uses OpenAI GPT-4 to analyze transcribed speech
 */

require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

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

// Auth
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$auth = new AuthController();
$user = $auth->me($token);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Token ungültig']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
$submissionId = $input['submission_id'] ?? 0;

if (!$submissionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Submission ID erforderlich']);
    exit;
}

$apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

if (empty($apiKey) || $apiKey === 'sk-test-key-placeholder') {
    http_response_code(500);
    echo json_encode(['error' => 'OpenAI API key nicht konfiguriert']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get submission with task info
    $submission = $db->selectOne(
        "SELECT s.*, qp.content as task_content 
         FROM speaking_submissions s
         LEFT JOIN question_pools qp ON s.task_id = qp.id
         WHERE s.id = ? AND s.user_id = ?",
        [$submissionId, $user['id']]
    );
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['error' => 'Aufnahme nicht gefunden']);
        exit;
    }
    
    // Check if transcription exists
    if (empty($submission['transcription'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Zuerst Transkription durchführen']);
        exit;
    }
    
    // Check if already analyzed
    if ($submission['status'] === 'analyzed' && !empty($submission['ai_analysis'])) {
        $analysis = json_decode($submission['ai_analysis'], true);
        echo json_encode([
            'success' => true,
            'submission_id' => $submissionId,
            'analysis' => $analysis,
            'from_cache' => true
        ]);
        exit;
    }
    
    // Update status
    $db->update('speaking_submissions', [
        'status' => 'analyzing',
        'analysis_started_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$submissionId]);
    
    // Get task prompt
    $taskContent = json_decode($submission['task_content'] ?? '{}', true);
    $taskPrompt = $taskContent['question'] ?? $taskContent['prompt'] ?? getDefaultTaskPrompt($submission['teil']);
    
    // Call OpenAI for analysis
    $analysis = analyzeSpeaking($apiKey, $submission['transcription'], $taskPrompt, $submission['teil']);
    
    // Update database
    $db->update('speaking_submissions', [
        'ai_analysis' => json_encode($analysis),
        'ai_score' => $analysis['overall_score'],
        'status' => 'analyzed',
        'analysis_completed_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$submissionId]);
    
    echo json_encode([
        'success' => true,
        'submission_id' => $submissionId,
        'analysis' => $analysis
    ]);
    
} catch (Exception $e) {
    error_log('Analysis error: ' . $e->getMessage());
    
    // Revert status on error
    if (isset($submissionId)) {
        $db->update('speaking_submissions', [
            'status' => 'transcribed'
        ], 'id = ?', [$submissionId]);
    }
    
    http_response_code(500);
    echo json_encode(['error' => 'Analyse fehlgeschlagen: ' . $e->getMessage()]);
}

/**
 * Analyze speaking using OpenAI GPT-4
 */
function analyzeSpeaking(string $apiKey, string $transcription, string $taskPrompt, string $teil): array
{
    $systemPrompt = getAnalysisSystemPrompt($teil);
    
    $userPrompt = sprintf(
        "AUFGABE:\n%s\n\nGESPROCHENER TEXT:\n%s\n\nAnalysiere den gesprochenen Text nach DTZ-Kriterien.",
        $taskPrompt,
        $transcription
    );
    
    $curl = curl_init();
    
    $data = [
        'model' => 'gpt-4o-mini',  // Using mini for cost efficiency
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => 0.3,
        'max_tokens' => 2000,
        'response_format' => ['type' => 'json_object']
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode !== 200) {
        throw new Exception('OpenAI API Error: HTTP ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '{}';
    
    $analysis = json_decode($content, true);
    
    // Ensure required fields exist
    return [
        'overall_score' => $analysis['overall_score'] ?? $analysis['score'] ?? 0,
        'fluency' => $analysis['fluency'] ?? ['score' => 0, 'feedback' => ''],
        'grammar' => $analysis['grammar'] ?? ['score' => 0, 'feedback' => ''],
        'vocabulary' => $analysis['vocabulary'] ?? ['score' => 0, 'feedback' => ''],
        'pronunciation_hints' => $analysis['pronunciation_hints'] ?? [],
        'task_completion' => $analysis['task_completion'] ?? ['score' => 0, 'feedback' => ''],
        'strengths' => $analysis['strengths'] ?? [],
        'improvements' => $analysis['improvements'] ?? [],
        'dtz_level' => $analysis['dtz_level'] ?? 'A2',
        'suggestions' => $analysis['suggestions'] ?? []
    ];
}

/**
 * Get system prompt for analysis
 */
function getAnalysisSystemPrompt(string $teil): string
{
    return <<<'PROMPT'
Du bist ein DTZ (Deutsch-Test für Zuwanderer) Prüfer. Analysiere den gesprochenen Text nach DTZ-Kriterien.

Bewerte auf einer Skala von 0-100:
- 0-49: Nicht bestanden
- 50-64: A2 Niveau (genügend)
- 65-79: B1 Niveau (gut)
- 80-100: B2+ Niveau (sehr gut)

Antworte im JSON-Format:
{
    "overall_score": number (0-100),
    "fluency": {
        "score": number (0-100),
        "feedback": "string - Bewertung des Flusses, Pausen, Füllwörter"
    },
    "grammar": {
        "score": number (0-100),
        "feedback": "string - Grammatikfehler und Korrektheit"
    },
    "vocabulary": {
        "score": number (0-100),
        "feedback": "string - Wortschatzvielfalt und Angemessenheit"
    },
    "task_completion": {
        "score": number (0-100),
        "feedback": "string - Wurde die Aufgabe vollständig erledigt?"
    },
    "pronunciation_hints": ["string array - Tipps zur Aussprache"],
    "strengths": ["string array - Was war gut?"],
    "improvements": ["string array - Was kann verbessert werden?"],
    "dtz_level": "string - A2, B1 oder B2",
    "suggestions": ["string array - Übungsvorschläge"]
}

Sprache der Rückmeldung: Deutsch
PROMPT;
}

/**
 * Get default task prompt based on Teil
 */
function getDefaultTaskPrompt(string $teil): string
{
    $prompts = [
        '1' => 'Teil 1: Stellen Sie sich vor. Sprechen Sie ca. 1 Minute über sich selbst.',
        '2' => 'Teil 2: Beschreiben Sie das Bild und sprechen Sie über eigene Erfahrungen.',
        '3' => 'Teil 3: Äußern Sie Ihre Meinung zum Thema und begründen Sie diese.'
    ];
    
    return $prompts[$teil] ?? 'Sprechen Sie frei zum gegebenen Thema.';
}
