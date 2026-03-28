<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

// CORS
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

$submissionId = $_GET['id'] ?? '';

if (empty($submissionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID erforderlich']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $submission = $db->selectOne(
        "SELECT 
            id, task_type, task_prompt, original_text, word_count,
            final_feedback, final_score, status, approved_at
         FROM writing_submissions 
         WHERE id = ? AND user_id = ? AND status = 'approved'",
        [$submissionId, $user['id']]
    );
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['error' => 'Feedback noch nicht verfügbar']);
        exit;
    }
    
    $feedback = json_decode($submission['final_feedback'] ?? '{}', true);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'submission' => [
            'id' => $submission['id'],
            'task_type' => $submission['task_type'],
            'task_prompt' => $submission['task_prompt'],
            'original_text' => $submission['original_text'],
            'word_count' => $submission['word_count'],
            'score' => $submission['final_score'],
            'approved_at' => $submission['approved_at']
        ],
        'feedback' => $feedback
    ]);
    
} catch (Exception $e) {
    error_log('Writing feedback error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden']);
}
