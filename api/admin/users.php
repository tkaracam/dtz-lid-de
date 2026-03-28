<?php
declare(strict_types=1);

// This includes auth check
require_once __DIR__ . '/auth.php';

require_once __DIR__ . '/../../src/Models/Admin.php';

use DTZ\Models\Admin;

$method = $_SERVER['REQUEST_METHOD'];

try {
    $adminModel = new Admin();
    
    switch ($method) {
        case 'GET':
            // List users
            $page = (int) ($_GET['page'] ?? 1);
            $perPage = (int) ($_GET['per_page'] ?? 20);
            $search = $_GET['search'] ?? '';
            
            $result = $adminModel->getUsers($page, $perPage, $search);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User-ID erforderlich']);
                exit;
            }
            
            // Prevent self-demotion if super admin
            if ($input['user_id'] == $adminUser['id'] && isset($input['is_admin']) && !$input['is_admin']) {
                if (!$adminModel->isSuperAdmin($adminUser['id'])) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Du kannst deinen eigenen Admin-Status nicht entfernen']);
                    exit;
                }
            }
            
            $success = $adminModel->updateUser($input['user_id'], $input);
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Benutzer aktualisiert']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Aktualisierung fehlgeschlagen']);
            }
            break;
            
        case 'DELETE':
            // Delete user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User-ID erforderlich']);
                exit;
            }
            
            // Prevent self-deletion
            if ($input['user_id'] == $adminUser['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Du kannst dich nicht selbst löschen']);
                exit;
            }
            
            // Only super admin can delete admins
            $targetUser = $adminModel->getUsers(1, 1, '');
            if ($adminModel->isAdmin($input['user_id']) && !$adminModel->isSuperAdmin($adminUser['id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Nur Super-Admins können andere Admins löschen']);
                exit;
            }
            
            $success = $adminModel->deleteUser($input['user_id']);
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Benutzer gelöscht']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Löschung fehlgeschlagen']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Methode nicht erlaubt']);
    }
    
} catch (Exception $e) {
    error_log('Admin users error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}
