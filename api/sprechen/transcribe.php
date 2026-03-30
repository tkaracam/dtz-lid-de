<?php
declare(strict_types=1);

/**
 * Speech-to-Text Transcription Endpoint
 * Uses OpenAI Whisper API to transcribe audio files
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
    
    // Get submission
    $submission = $db->selectOne(
        "SELECT * FROM speaking_submissions WHERE id = ? AND user_id = ?",
        [$submissionId, $user['id']]
    );
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['error' => 'Aufnahme nicht gefunden']);
        exit;
    }
    
    // Check if already transcribed
    if ($submission['status'] === 'transcribed' && !empty($submission['transcription'])) {
        echo json_encode([
            'success' => true,
            'submission_id' => $submissionId,
            'transcription' => $submission['transcription'],
            'from_cache' => true
        ]);
        exit;
    }
    
    // Update status
    $db->update('speaking_submissions', [
        'status' => 'transcribing',
        'transcription_started_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$submissionId]);
    
    // Get audio file path
    $audioPath = __DIR__ . '/../../' . $submission['audio_path'];
    
    if (!file_exists($audioPath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Audio-Datei nicht gefunden']);
        exit;
    }
    
    // Call OpenAI Whisper API
    $transcription = callWhisperAPI($apiKey, $audioPath);
    
    // Update database
    $db->update('speaking_submissions', [
        'transcription' => $transcription,
        'status' => 'transcribed',
        'transcription_completed_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$submissionId]);
    
    echo json_encode([
        'success' => true,
        'submission_id' => $submissionId,
        'transcription' => $transcription,
        'duration_seconds' => $submission['duration_seconds'],
        'word_count' => str_word_count($transcription)
    ]);
    
} catch (Exception $e) {
    error_log('Transcription error: ' . $e->getMessage());
    
    // Revert status on error
    if (isset($submissionId)) {
        $db->update('speaking_submissions', [
            'status' => 'uploaded'
        ], 'id = ?', [$submissionId]);
    }
    
    http_response_code(500);
    echo json_encode(['error' => 'Transkription fehlgeschlagen: ' . $e->getMessage()]);
}

/**
 * Call OpenAI Whisper API for transcription
 */
function callWhisperAPI(string $apiKey, string $audioPath): string
{
    $curl = curl_init();
    
    $postFields = [
        'file' => new CURLFile($audioPath),
        'model' => 'whisper-1',
        'language' => 'de',  // German language
        'response_format' => 'text'
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception('CURL Error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Whisper API Error: HTTP ' . $httpCode . ' - ' . $response);
    }
    
    return trim($response);
}
