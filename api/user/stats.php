<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';

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

try {
    $db = Database::getInstance();
    
    // Get total questions answered
    $questionsAnswered = $db->selectOne(
        "SELECT COUNT(*) as count FROM user_progress WHERE user_id = ?",
        [$user['id']]
    );
    
    // Get accuracy
    $accuracy = $db->selectOne(
        "SELECT 
            COUNT(CASE WHEN is_correct = TRUE THEN 1 END) * 100.0 / COUNT(*) as accuracy
         FROM user_progress 
         WHERE user_id = ? AND is_correct IS NOT NULL",
        [$user['id']]
    );
    
    // Get modelltest count
    $testCount = $db->selectOne(
        "SELECT COUNT(*) as count FROM modelltest_attempts WHERE user_id = ? AND status = 'completed'",
        [$user['id']]
    );
    
    // Get streak (simplified - last 7 days activity)
    $streak = $db->selectOne(
        "SELECT COUNT(DISTINCT DATE(created_at)) as days 
         FROM user_progress 
         WHERE user_id = ? AND created_at > NOW() - INTERVAL '7 days'",
        [$user['id']]
    );
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_questions' => (int)($questionsAnswered['count'] ?? 0),
            'accuracy' => round((float)($accuracy['accuracy'] ?? 0)),
            'modelltests' => (int)($testCount['count'] ?? 0),
            'streak' => (int)($streak['days'] ?? 0)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden']);
}
