<?php
declare(strict_types=1);

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

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin erforderlich']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get stats
    $stats = [
        'users' => [
            'total' => $db->selectOne("SELECT COUNT(*) as c FROM users")['c'],
            'active_today' => $db->selectOne("SELECT COUNT(DISTINCT user_id) as c FROM user_progress WHERE created_at > date('now', '-1 day')")['c'],
            'new_this_week' => $db->selectOne("SELECT COUNT(*) as c FROM users WHERE created_at > date('now', '-7 days')")['c'],
        ],
        'questions' => [
            'total' => $db->selectOne("SELECT COUNT(*) as c FROM question_pools")['c'],
            'by_module' => $db->select("SELECT module, COUNT(*) as count FROM question_pools GROUP BY module"),
        ],
        'tests' => [
            'total_attempts' => $db->selectOne("SELECT COUNT(*) as c FROM modelltest_attempts")['c'],
            'completed' => $db->selectOne("SELECT COUNT(*) as c FROM modelltest_attempts WHERE status = 'completed'")['c'],
            'avg_score' => round($db->selectOne("SELECT AVG(total_score) as c FROM modelltest_attempts WHERE status = 'completed'")['c'] ?? 0, 1),
        ],
        'writing' => [
            'pending' => $db->selectOne("SELECT COUNT(*) as c FROM writing_submissions WHERE status = 'pending'")['c'],
            'ai_reviewed' => $db->selectOne("SELECT COUNT(*) as c FROM writing_submissions WHERE status = 'ai_reviewed'")['c'],
            'approved' => $db->selectOne("SELECT COUNT(*) as c FROM writing_submissions WHERE status = 'approved'")['c'],
        ],
        'activity' => $db->select("
            SELECT date(created_at) as date, COUNT(*) as count 
            FROM user_progress 
            WHERE created_at > date('now', '-30 days')
            GROUP BY date(created_at)
            ORDER BY date DESC
            LIMIT 30
        ")
    ];
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden: ' . $e->getMessage()]);
}
