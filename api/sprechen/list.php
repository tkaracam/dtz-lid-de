<?php
declare(strict_types=1);

/**
 * Speaking Submissions List Endpoint
 * Returns user's speaking practice history
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

// Query params
$teil = $_GET['teil'] ?? null;
$limit = min((int)($_GET['limit'] ?? 20), 100);
$offset = (int)($_GET['offset'] ?? 0);

try {
    $db = Database::getInstance();
    
    // Build query
    $where = 'WHERE user_id = ?';
    $params = [$user['id']];
    
    if ($teil) {
        $where .= ' AND teil = ?';
        $params[] = $teil;
    }
    
    // Get total count
    $countResult = $db->selectOne(
        "SELECT COUNT(*) as total FROM speaking_submissions $where",
        $params
    );
    $total = $countResult['total'] ?? 0;
    
    // Get submissions
    $submissions = $db->select(
        "SELECT 
            id,
            task_id,
            teil,
            duration_seconds,
            transcription,
            ai_score,
            status,
            created_at,
            transcription_completed_at,
            analysis_completed_at
         FROM speaking_submissions
         $where
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    );
    
    // Format results
    $formatted = array_map(function ($s) {
        return [
            'id' => (int)$s['id'],
            'task_id' => $s['task_id'],
            'teil' => $s['teil'],
            'duration_seconds' => (int)$s['duration_seconds'],
            'has_transcription' => !empty($s['transcription']),
            'transcription_preview' => $s['transcription'] ? substr($s['transcription'], 0, 100) . '...' : null,
            'ai_score' => $s['ai_score'] ? (int)$s['ai_score'] : null,
            'status' => $s['status'],
            'created_at' => $s['created_at'],
            'analyzed' => $s['status'] === 'analyzed'
        ];
    }, $submissions);
    
    // Get stats by teil
    $stats = $db->select(
        "SELECT 
            teil,
            COUNT(*) as total,
            AVG(ai_score) as avg_score,
            MAX(ai_score) as best_score
         FROM speaking_submissions
         WHERE user_id = ? AND status = 'analyzed'
         GROUP BY teil",
        [$user['id']]
    );
    
    echo json_encode([
        'success' => true,
        'submissions' => $formatted,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'stats_by_teil' => $stats
    ]);
    
} catch (Exception $e) {
    error_log('List error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden: ' . $e->getMessage()]);
}
