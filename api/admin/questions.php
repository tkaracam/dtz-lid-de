<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

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
            if (isset($_GET['id'])) {
                // Single question
                $question = $db->selectOne(
                    "SELECT * FROM question_pools WHERE id = ?",
                    [$_GET['id']]
                );
                
                if (!$question) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Frage nicht gefunden']);
                    exit;
                }
                
                // Decode JSON fields
                $question['content'] = json_decode($question['content'], true);
                $question['correct_answer'] = json_decode($question['correct_answer'], true);
                $question['media_urls'] = json_decode($question['media_urls'], true);
                $question['hints'] = json_decode($question['hints'], true);
                
                echo json_encode(['success' => true, 'question' => $question]);
            } else {
                // List questions
                $page = max(1, intval($_GET['page'] ?? 1));
                $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
                $offset = ($page - 1) * $limit;
                $module = $_GET['module'] ?? '';
                $level = $_GET['level'] ?? '';
                
                $where = ['is_active = 1'];
                $params = [];
                
                if ($module) {
                    $where[] = 'module = ?';
                    $params[] = $module;
                }
                if ($level) {
                    $where[] = 'level = ?';
                    $params[] = $level;
                }
                
                $whereStr = implode(' AND ', $where);
                
                $questions = $db->select(
                    "SELECT id, module, teil, level, question_type, difficulty, points, is_active, created_at 
                     FROM question_pools 
                     WHERE $whereStr
                     ORDER BY created_at DESC 
                     LIMIT ? OFFSET ?",
                    array_merge($params, [$limit, $offset])
                );
                
                $total = $db->selectOne(
                    "SELECT COUNT(*) as c FROM question_pools WHERE $whereStr",
                    $params
                )['c'];
                
                echo json_encode([
                    'success' => true,
                    'questions' => $questions,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $limit)
                ]);
            }
            break;
            
        case 'POST':
            // Create question
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = ['module', 'teil', 'level', 'question_type', 'content', 'correct_answer'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Feld '$field' ist erforderlich"]);
                    exit;
                }
            }
            
            $questionId = $db->insert('question_pools', [
                'module' => $input['module'],
                'teil' => $input['teil'],
                'level' => $input['level'],
                'question_type' => $input['question_type'],
                'content' => is_string($input['content']) ? $input['content'] : json_encode($input['content']),
                'correct_answer' => is_string($input['correct_answer']) ? $input['correct_answer'] : json_encode($input['correct_answer']),
                'explanation' => $input['explanation'] ?? '',
                'difficulty' => $input['difficulty'] ?? 5,
                'points' => $input['points'] ?? 5,
                'is_active' => $input['is_active'] ?? 1,
                'created_by' => $user['id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Frage erstellt',
                'id' => $questionId
            ]);
            break;
            
        case 'PUT':
            // Update question
            $input = json_decode(file_get_contents('php://input'), true);
            $questionId = $input['id'] ?? 0;
            
            if (!$questionId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID erforderlich']);
                exit;
            }
            
            $allowedFields = ['module', 'teil', 'level', 'question_type', 'content', 'correct_answer', 
                             'explanation', 'difficulty', 'points', 'is_active', 'is_premium_only'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $value = $input[$field];
                    // Encode JSON fields
                    if (in_array($field, ['content', 'correct_answer', 'media_urls', 'hints']) && !is_string($value)) {
                        $value = json_encode($value);
                    }
                    $updateData[$field] = $value;
                }
            }
            
            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['error' => 'Keine Daten zum Aktualisieren']);
                exit;
            }
            
            $db->update('question_pools', $updateData, 'id = ?', [$questionId]);
            
            echo json_encode(['success' => true, 'message' => 'Frage aktualisiert']);
            break;
            
        case 'DELETE':
            // Delete question (soft delete)
            $questionId = $_GET['id'] ?? 0;
            
            if (!$questionId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID erforderlich']);
                exit;
            }
            
            $db->update('question_pools', ['is_active' => 0], 'id = ?', [$questionId]);
            
            echo json_encode(['success' => true, 'message' => 'Frage deaktiviert']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler: ' . $e->getMessage()]);
}
