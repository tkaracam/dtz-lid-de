<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
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

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all submissions with user info
            $status = $_GET['status'] ?? '';
            $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
            
            $where = '';
            $params = [];
            if ($status) {
                $where = 'WHERE ws.status = ?';
                $params = [$status];
            }
            
            $submissions = $db->select(
                "SELECT ws.id, ws.task_type, ws.word_count, ws.status, ws.ai_score, ws.final_score, 
                        ws.submitted_at, u.display_name as user_name, u.email as user_email
                 FROM writing_submissions ws
                 JOIN users u ON ws.user_id = u.id
                 $where
                 ORDER BY ws.submitted_at DESC
                 LIMIT ?",
                array_merge($params, [$limit])
            );
            
            echo json_encode(['success' => true, 'submissions' => $submissions]);
            break;
            
        case 'PUT':
            // Update submission with admin review
            $input = json_decode(file_get_contents('php://input'), true);
            $submissionId = $input['id'] ?? 0;
            
            if (!$submissionId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID erforderlich']);
                exit;
            }
            
            $updateData = [
                'admin_id' => $user['id'],
                'admin_reviewed_at' => date('Y-m-d H:i:s')
            ];
            
            if (isset($input['score'])) {
                $updateData['admin_score'] = $input['score'];
                $updateData['final_score'] = $input['score']; // Admin score is final
            }
            
            if (isset($input['status'])) {
                $updateData['status'] = $input['status'];
                
                if ($input['status'] === 'approved') {
                    $updateData['approved_at'] = date('Y-m-d H:i:s');
                    $updateData['user_notified'] = 1;
                }
            }
            
            if (isset($input['comment'])) {
                $updateData['admin_comment'] = $input['comment'];
            }
            
            $db->update('writing_submissions', $updateData, 'id = ?', [$submissionId]);
            
            echo json_encode(['success' => true, 'message' => 'Bewertung gespeichert']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler: ' . $e->getMessage()]);
}
