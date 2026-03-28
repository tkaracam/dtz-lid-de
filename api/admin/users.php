<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');

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
            // List users
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            
            $where = '';
            $params = [];
            if ($search) {
                $where = "WHERE email LIKE ? OR display_name LIKE ?";
                $params = ["%$search%", "%$search%"];
            }
            
            $users = $db->select(
                "SELECT id, email, display_name, level, subscription_status, is_active, created_at, last_activity_at 
                 FROM users $where 
                 ORDER BY created_at DESC 
                 LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            );
            
            $total = $db->selectOne(
                "SELECT COUNT(*) as c FROM users $where",
                $params
            )['c'];
            
            echo json_encode([
                'success' => true,
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            break;
            
        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['id'] ?? 0;
            
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID erforderlich']);
                exit;
            }
            
            $allowedFields = ['display_name', 'level', 'subscription_status', 'is_active', 'daily_goal'];
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }
            
            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Keine Daten zum Aktualisieren']);
                exit;
            }
            
            $db->update('users', $updateData, 'id = ?', [$userId]);
            
            echo json_encode(['success' => true, 'message' => 'Benutzer aktualisiert']);
            break;
            
        case 'DELETE':
            // Delete user
            $userId = $_GET['id'] ?? 0;
            
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID erforderlich']);
                exit;
            }
            
            // Prevent self-deletion
            if ($userId == $user['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Eigener Account kann nicht gelöscht werden']);
                exit;
            }
            
            $db->delete('users', 'id = ?', [$userId]);
            
            echo json_encode(['success' => true, 'message' => 'Benutzer gelöscht']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler: ' . $e->getMessage()]);
}
