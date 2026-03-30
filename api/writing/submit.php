<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../../src/Security/InputValidator.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;
use DTZ\Security\SecurityHeaders;
use DTZ\Security\InputValidator;

// Set security headers
SecurityHeaders::set();
SecurityHeaders::setCors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    SecurityHeaders::jsonResponse(['error' => 'Nur POST erlaubt'], 405);
}

// Rate limiting
$clientIp = InputValidator::getClientIp();
if (!InputValidator::rateLimit('writing_' . $clientIp, 10, 60)) {
    SecurityHeaders::jsonResponse(['error' => 'Zu viele Anfragen'], 429);
}

// Auth check
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    SecurityHeaders::jsonResponse(['error' => 'Nicht autorisiert'], 401);
}

$auth = new AuthController();
$user = $auth->me($token);

if (!$user) {
    SecurityHeaders::jsonResponse(['error' => 'Token ungültig'], 401);
}

// Get and validate input
$input = InputValidator::getJsonInput();
if (!$input) {
    SecurityHeaders::jsonResponse(['error' => 'Ungültige Eingabe'], 400);
}

$taskType = InputValidator::string($input['task_type'] ?? '', 50);
$text = InputValidator::text($input['text'] ?? '', 5000);
$teil = InputValidator::int($input['teil'] ?? 1, 1, 2) ?? 1;

if (empty($taskType) || empty($text)) {
    SecurityHeaders::jsonResponse(['error' => 'Aufgabe und Text erforderlich'], 400);
}

$validTasks = ['bewerbung', 'beschwerde', 'anfrage', 'termin', 'einladung', 'danksagung'];
if (!in_array($taskType, $validTasks, true)) {
    SecurityHeaders::jsonResponse(['error' => 'Ungültige Aufgabe'], 400);
}

// Check for suspicious content
if (InputValidator::hasXss($text) || InputValidator::hasSqlInjection($text)) {
    SecurityHeaders::jsonResponse(['error' => 'Ungültiger Textinhalt'], 400);
}

try {
    $db = Database::getInstance();
    
    $id = $db->insert('writing_submissions', [
        'user_id' => $user['id'],
        'task_type' => $taskType,
        'text' => $text,
        'teil' => $teil,
        'status' => 'pending',
        'word_count' => str_word_count($text)
    ]);
    
    SecurityHeaders::jsonResponse([
        'success' => true,
        'submission_id' => $id,
        'status' => 'pending'
    ]);
    
} catch (Exception $e) {
    error_log('Writing submission error: ' . $e->getMessage());
    SecurityHeaders::jsonResponse(['error' => 'Fehler beim Speichern'], 500);
}
