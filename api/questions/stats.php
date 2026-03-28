<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Models/Session.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Models/Question.php';
require_once __DIR__ . '/../../src/Models/UserAnswer.php';
require_once __DIR__ . '/../../src/Models/DailyStats.php';
require_once __DIR__ . '/../../src/Questions/QuestionController.php';

use DTZ\Questions\QuestionController;
use DTZ\Auth\AuthController;

// CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur GET erlaubt']);
    exit;
}

// Authenticate user
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
    echo json_encode(['error' => 'Token ungültig oder abgelaufen']);
    exit;
}

// Get dashboard stats
try {
    $controller = new QuestionController();
    $result = $controller->dashboard($user);
    
    http_response_code(200);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Stats error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}
