<?php
/**
 * List all questions with filtering - Admin only
 */

require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/JWT.php';

use DTZ\Database\Database;
use DTZ\Auth\JWT;

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

// Get and verify token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';

if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token erforderlich']);
    exit;
}

// Get JWT secret
$jwtSecret = $_ENV['JWT_SECRET'] ?? null;
if (!$jwtSecret) {
    $secretFile = __DIR__ . '/../.jwt_secret';
    if (file_exists($secretFile)) {
        $jwtSecret = trim(file_get_contents($secretFile));
    } else {
        $jwtSecret = 'dtz-learning-secret-key-change-in-production';
    }
}

try {
    // Verify token
    $jwt = new JWT($jwtSecret);
    $payload = $jwt->verify($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Ungültiger Token']);
        exit;
    }
    
    // Check if admin
    if (($payload['role'] ?? '') !== 'admin' && ($payload['role'] ?? '') !== 'owner') {
        http_response_code(403);
        echo json_encode(['error' => 'Nur Admin-Benutzer dürfen alle Fragen sehen']);
        exit;
    }
    
    // Get filter parameters
    $module = $_GET['module'] ?? '';
    $teil = $_GET['teil'] ?? '';
    $level = $_GET['level'] ?? '';
    $search = $_GET['search'] ?? '';
    $limit = intval($_GET['limit'] ?? 100);
    $offset = intval($_GET['offset'] ?? 0);
    
    // Connect to database
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    // Build query
    $where = ['is_active = 1'];
    $params = [];
    
    if (!empty($module)) {
        $where[] = 'module = ?';
        $params[] = $module;
    }
    
    if (!empty($teil)) {
        $where[] = 'teil = ?';
        $params[] = $teil;
    }
    
    if (!empty($level)) {
        $where[] = 'level = ?';
        $params[] = $level;
    }
    
    if (!empty($search)) {
        $where[] = '(content LIKE ? OR explanation LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM question_pools WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    // Get questions
    $sql = "SELECT id, module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, created_at 
            FROM question_pools 
            WHERE $whereClause
            ORDER BY module, teil, id
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();
    
    // Parse JSON content
    foreach ($questions as &$q) {
        $q['content'] = json_decode($q['content'], true);
        $q['correct_answer'] = json_decode($q['correct_answer'], true);
    }
    
    // Get module counts
    $moduleCounts = $pdo->query("SELECT module, COUNT(*) as count FROM question_pools WHERE is_active = 1 GROUP BY module")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode([
        'success' => true,
        'total' => intval($totalCount),
        'limit' => $limit,
        'offset' => $offset,
        'counts' => [
            'total' => intval($totalCount),
            'lesen' => intval($moduleCounts['lesen'] ?? 0),
            'hoeren' => intval($moduleCounts['hoeren'] ?? 0),
            'schreiben' => intval($moduleCounts['schreiben'] ?? 0),
            'sprechen' => intval($moduleCounts['sprechen'] ?? 0),
            'lid' => intval($moduleCounts['lid'] ?? 0)
        ],
        'questions' => $questions
    ]);
    
} catch (Exception $e) {
    error_log('Question list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()]);
}
