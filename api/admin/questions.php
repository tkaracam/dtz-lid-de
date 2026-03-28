<?php
declare(strict_types=1);

// This includes auth check
require_once __DIR__ . '/auth.php';

require_once __DIR__ . '/../../src/Models/QuestionAdmin.php';

use DTZ\Models\QuestionAdmin;

$method = $_SERVER['REQUEST_METHOD'];

try {
    $questionAdmin = new QuestionAdmin();
    
    switch ($method) {
        case 'GET':
            // List questions
            $page = (int) ($_GET['page'] ?? 1);
            $perPage = (int) ($_GET['per_page'] ?? 20);
            
            $filters = [];
            if (!empty($_GET['module'])) $filters['module'] = $_GET['module'];
            if (!empty($_GET['level'])) $filters['level'] = $_GET['level'];
            if (!empty($_GET['teil'])) $filters['teil'] = (int) $_GET['teil'];
            if (isset($_GET['is_active'])) $filters['is_active'] = (bool) $_GET['is_active'];
            if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
            
            $result = $questionAdmin->getAll($page, $perPage, $filters);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'POST':
            // Create question
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['module']) || empty($input['content']) || empty($input['correct_answer'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Modul, Inhalt und richtige Antwort sind erforderlich']);
                exit;
            }
            
            $input['created_by'] = $adminUser['id'];
            $id = $questionAdmin->create($input);
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Frage erstellt',
                'id' => $id
            ]);
            break;
            
        case 'PUT':
            // Update question
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Frage-ID erforderlich']);
                exit;
            }
            
            $success = $questionAdmin->update($input['id'], $input);
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Frage aktualisiert']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Aktualisierung fehlgeschlagen']);
            }
            break;
            
        case 'DELETE':
            // Delete question
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Frage-ID erforderlich']);
                exit;
            }
            
            $success = $questionAdmin->delete($input['id']);
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Frage gelöscht']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Löschung fehlgeschlagen']);
            }
            break;
            
        case 'PATCH':
            // Toggle active status
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Frage-ID erforderlich']);
                exit;
            }
            
            $success = $questionAdmin->toggleActive($input['id']);
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Status geändert']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Änderung fehlgeschlagen']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Methode nicht erlaubt']);
    }
    
} catch (Exception $e) {
    error_log('Admin questions error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}
