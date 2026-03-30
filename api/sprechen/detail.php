<?php
declare(strict_types=1);

/**
 * Speaking Submission Detail Endpoint
 * Returns full details including audio URL, transcription, and analysis
 */

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur GET erlaubt']);
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

// Get submission ID from URL
$submissionId = (int)($_GET['id'] ?? 0);

if (!$submissionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Submission ID erforderlich']);
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
    
    // Build response
    $response = [
        'id' => (int)$submission['id'],
        'task_id' => $submission['task_id'],
        'teil' => $submission['teil'],
        'duration_seconds' => (int)$submission['duration_seconds'],
        'status' => $submission['status'],
        'created_at' => $submission['created_at'],
        'timings' => [
            'uploaded_at' => $submission['created_at'],
            'transcription_started' => $submission['transcription_started_at'],
            'transcription_completed' => $submission['transcription_completed_at'],
            'analysis_started' => $submission['analysis_started_at'],
            'analysis_completed' => $submission['analysis_completed_at']
        ]
    ];
    
    // Add audio URL
    if ($submission['audio_path']) {
        // Create a temporary token for audio access
        $audioToken = base64_encode(json_encode([
            'sub' => $submissionId,
            'user' => $user['id'],
            'exp' => time() + 3600  // 1 hour expiry
        ]));
        
        $response['audio_url'] = '/api/sprechen/audio.php?token=' . urlencode($audioToken);
        $response['mime_type'] = $submission['mime_type'];
    }
    
    // Add transcription if available
    if (!empty($submission['transcription'])) {
        $response['transcription'] = $submission['transcription'];
        $response['word_count'] = str_word_count($submission['transcription']);
    }
    
    // Add analysis if available
    if (!empty($submission['ai_analysis'])) {
        $analysis = json_decode($submission['ai_analysis'], true);
        $response['analysis'] = $analysis;
    }
    
    echo json_encode([
        'success' => true,
        'submission' => $response
    ]);
    
} catch (Exception $e) {
    error_log('Detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden: ' . $e->getMessage()]);
}
