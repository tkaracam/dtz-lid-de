<?php
declare(strict_types=1);

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

// Auth check
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

$input = json_decode(file_get_contents('php://input'), true);
$taskType = $input['task_type'] ?? '';
$text = $input['text'] ?? '';

if (empty($taskType) || empty($text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Aufgabe und Text erforderlich']);
    exit;
}

$validTasks = ['bewerbung', 'beschwerde', 'anfrage', 'termin', 'einladung', 'danksagung'];
if (!in_array($taskType, $validTasks)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiger Aufgabentyp']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Word count
    $wordCount = str_word_count($text);
    
    // Create submission
    $submissionId = $db->insert('writing_submissions', [
        'user_id' => $user['id'],
        'task_type' => $taskType,
        'task_prompt' => $taskType,
        'original_text' => $text,
        'word_count' => $wordCount,
        'status' => 'pending'
    ]);
    
    // Add to job queue for AI processing (optional - can be processed immediately or via cron)
    // For now, we'll just mark it as pending for manual review or background processing
    
    echo json_encode([
        'success' => true,
        'message' => 'Text eingereicht',
        'submission_id' => $submissionId,
        'status' => 'pending'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Speichern: ' . $e->getMessage()]);
}
