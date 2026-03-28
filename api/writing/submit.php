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

// Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['text']) || empty($input['task_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Text und Aufgabentyp erforderlich']);
    exit;
}

$text = trim($input['text']);
$taskType = $input['task_type'];
$taskPrompt = $input['task_prompt'] ?? getDefaultPrompt($taskType);

// Validate
$wordCount = str_word_count($text);
if ($wordCount < 30) {
    http_response_code(422);
    echo json_encode(['error' => 'Text zu kurz (mindestens 30 Wörter)']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $submissionId = $db->insert('writing_submissions', [
        'user_id' => $user['id'],
        'task_type' => $taskType,
        'task_prompt' => $taskPrompt,
        'original_text' => $text,
        'status' => 'pending',
        'submitted_at' => date('Y-m-d H:i:s')
    ]);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Text eingereicht. Warte auf Korrektur.',
        'submission_id' => $submissionId,
        'status' => 'pending',
        'word_count' => $wordCount,
        'estimated_review_time' => '24 Stunden'
    ]);
    
} catch (Exception $e) {
    error_log('Writing submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}

function getDefaultPrompt(string $taskType): string
{
    $prompts = [
        'bewerbung' => 'Schreiben Sie eine Bewerbung.',
        'beschwerde' => 'Schreiben Sie einen Beschwerdebrief.',
        'anfrage' => 'Schreiben Sie eine Anfrage.',
        'termin' => 'Schreiben Sie eine Terminabsage.',
        'einladung' => 'Schreiben Sie eine Einladung.',
        'danksagung' => 'Schreiben Sie einen Dankesbrief.'
    ];
    return $prompts[$taskType] ?? 'Schreiben Sie einen Brief.';
}
