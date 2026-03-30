<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../../src/Security/InputValidator.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;
use DTZ\Security\SecurityHeaders;
use DTZ\Security\InputValidator;

SecurityHeaders::set();
SecurityHeaders::setCors();

// Check admin authentication
$auth = new AuthController();
$adminUser = $auth->authenticate();

if (!$adminUser || $adminUser['role'] !== 'admin') {
    SecurityHeaders::jsonResponse(['error' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? InputValidator::int($_GET['id']) : null;
            
            if ($id) {
                // Get specific question
                $question = $db->selectOne("
                    SELECT * FROM question_pools WHERE id = ?
                ", [$id]);
                
                if (!$question) {
                    SecurityHeaders::jsonResponse(['error' => 'Question not found'], 404);
                }
                
                // Parse JSON fields
                $question['content'] = json_decode($question['content'], true);
                $question['correct_answer'] = json_decode($question['correct_answer'], true);
                $question['media_urls'] = json_decode($question['media_urls'] ?? 'null', true);
                $question['hints'] = json_decode($question['hints'] ?? 'null', true);
                
                SecurityHeaders::jsonResponse(['question' => $question]);
            }
            
            // List questions with filters
            $page = max(1, InputValidator::int($_GET['page'] ?? 1) ?: 1);
            $perPage = min(100, max(10, InputValidator::int($_GET['per_page'] ?? 20) ?: 20));
            $offset = ($page - 1) * $perPage;
            
            $module = InputValidator::string($_GET['module'] ?? '', 20);
            $teil = InputValidator::int($_GET['teil'] ?? null);
            $level = InputValidator::string($_GET['level'] ?? '', 2);
            $isActive = isset($_GET['active']) ? ($_GET['active'] === '1' ? 1 : 0) : null;
            $search = InputValidator::string($_GET['search'] ?? '', 200);
            
            // Build query
            $where = ['1=1'];
            $params = [];
            
            if ($module) {
                $where[] = 'module = ?';
                $params[] = $module;
            }
            
            if ($teil) {
                $where[] = 'teil = ?';
                $params[] = $teil;
            }
            
            if ($level) {
                $where[] = 'level = ?';
                $params[] = $level;
            }
            
            if ($isActive !== null) {
                $where[] = 'is_active = ?';
                $params[] = $isActive;
            }
            
            if ($search) {
                $where[] = '(content LIKE ? OR explanation LIKE ?)';
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Get total count
            $countResult = $db->selectOne("SELECT COUNT(*) as total FROM question_pools WHERE $whereClause", $params);
            $total = (int)$countResult['total'];
            
            // Get questions
            $questions = $db->selectAll("
                SELECT id, module, teil, level, question_type, is_active, 
                       is_premium_only, difficulty, points, usage_count, 
                       created_at, updated_at
                FROM question_pools 
                WHERE $whereClause
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ", array_merge($params, [$perPage, $offset]));
            
            // Get module counts for sidebar
            $moduleCounts = $db->selectAll("
                SELECT module, COUNT(*) as count 
                FROM question_pools 
                GROUP BY module
            ");
            
            SecurityHeaders::jsonResponse([
                'questions' => $questions,
                'module_counts' => array_column($moduleCounts, 'count', 'module'),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'POST':
            // Create new question
            $input = InputValidator::getJsonInput();
            if (!$input) {
                SecurityHeaders::jsonResponse(['error' => 'Invalid input'], 400);
            }
            
            // Validate required fields
            $module = InputValidator::string($input['module'] ?? '', 20);
            $teil = InputValidator::int($input['teil'] ?? null);
            $level = InputValidator::string($input['level'] ?? '', 2);
            $questionType = InputValidator::string($input['question_type'] ?? '', 30);
            $content = $input['content'] ?? null;
            $correctAnswer = $input['correct_answer'] ?? null;
            
            if (!$module || !$teil || !$level || !$questionType || !$content || !$correctAnswer) {
                SecurityHeaders::jsonResponse(['error' => 'Missing required fields'], 400);
            }
            
            // Validate module
            $validModules = ['lesen', 'hoeren', 'schreiben', 'sprechen', 'lid'];
            if (!in_array($module, $validModules)) {
                SecurityHeaders::jsonResponse(['error' => 'Invalid module'], 400);
            }
            
            // Validate level
            $validLevels = ['A1', 'A2', 'B1', 'B2'];
            if (!in_array($level, $validLevels)) {
                SecurityHeaders::jsonResponse(['error' => 'Invalid level'], 400);
            }
            
            // Build insert data
            $data = [
                'module' => $module,
                'teil' => $teil,
                'level' => $level,
                'question_type' => $questionType,
                'content' => json_encode($content),
                'correct_answer' => json_encode($correctAnswer),
                'explanation' => InputValidator::text($input['explanation'] ?? '', 2000),
                'difficulty' => InputValidator::int($input['difficulty'] ?? 5, 1, 10) ?? 5,
                'points' => InputValidator::int($input['points'] ?? 10, 1, 100) ?? 10,
                'is_active' => isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 1,
                'is_premium_only' => isset($input['is_premium_only']) ? ($input['is_premium_only'] ? 1 : 0) : 0,
                'created_by' => $adminUser['id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Optional fields
            if (isset($input['media_urls'])) {
                $data['media_urls'] = json_encode($input['media_urls']);
            }
            if (isset($input['hints'])) {
                $data['hints'] = json_encode($input['hints']);
            }
            
            $questionId = $db->insert('question_pools', $data);
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => 'question_created',
                'entity_type' => 'question',
                'entity_id' => $questionId,
                'new_values' => json_encode(['module' => $module, 'teil' => $teil, 'level' => $level]),
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'question_id' => $questionId,
                'message' => 'Question created successfully'
            ], 201);
            break;
            
        case 'PUT':
            // Update question
            $input = InputValidator::getJsonInput();
            if (!$input || empty($input['id'])) {
                SecurityHeaders::jsonResponse(['error' => 'Question ID required'], 400);
            }
            
            $id = InputValidator::int($input['id']);
            $question = $db->selectOne("SELECT * FROM question_pools WHERE id = ?", [$id]);
            
            if (!$question) {
                SecurityHeaders::jsonResponse(['error' => 'Question not found'], 404);
            }
            
            $updates = [];
            $oldValues = [];
            $newValues = [];
            
            $updatableFields = [
                'module' => ['type' => 'string', 'max' => 20],
                'teil' => ['type' => 'int'],
                'level' => ['type' => 'string', 'max' => 2],
                'question_type' => ['type' => 'string', 'max' => 30],
                'explanation' => ['type' => 'text', 'max' => 2000],
                'difficulty' => ['type' => 'int', 'min' => 1, 'max' => 10],
                'points' => ['type' => 'int', 'min' => 1, 'max' => 100],
                'is_active' => ['type' => 'bool'],
                'is_premium_only' => ['type' => 'bool']
            ];
            
            foreach ($updatableFields as $field => $config) {
                if (isset($input[$field])) {
                    $oldValues[$field] = $question[$field];
                    
                    if ($config['type'] === 'string') {
                        $updates[$field] = InputValidator::string($input[$field], $config['max']);
                    } elseif ($config['type'] === 'int') {
                        $updates[$field] = InputValidator::int($input[$field], $config['min'] ?? null, $config['max'] ?? null);
                    } elseif ($config['type'] === 'text') {
                        $updates[$field] = InputValidator::text($input[$field], $config['max']);
                    } elseif ($config['type'] === 'bool') {
                        $updates[$field] = $input[$field] ? 1 : 0;
                    }
                    
                    $newValues[$field] = $updates[$field];
                }
            }
            
            // JSON fields
            if (isset($input['content'])) {
                $updates['content'] = json_encode($input['content']);
                $oldValues['content'] = '[JSON]';
                $newValues['content'] = '[JSON]';
            }
            if (isset($input['correct_answer'])) {
                $updates['correct_answer'] = json_encode($input['correct_answer']);
                $oldValues['correct_answer'] = '[JSON]';
                $newValues['correct_answer'] = '[JSON]';
            }
            if (isset($input['media_urls'])) {
                $updates['media_urls'] = json_encode($input['media_urls']);
            }
            if (isset($input['hints'])) {
                $updates['hints'] = json_encode($input['hints']);
            }
            
            if (empty($updates)) {
                SecurityHeaders::jsonResponse(['error' => 'No fields to update'], 400);
            }
            
            $updates['updated_at'] = date('Y-m-d H:i:s');
            
            $db->update('question_pools', $updates, 'id = ?', [$id]);
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => 'question_updated',
                'entity_type' => 'question',
                'entity_id' => $id,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($newValues),
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'message' => 'Question updated successfully'
            ]);
            break;
            
        case 'DELETE':
            // Delete question
            $id = InputValidator::int($_GET['id'] ?? null);
            if (!$id) {
                SecurityHeaders::jsonResponse(['error' => 'Question ID required'], 400);
            }
            
            $question = $db->selectOne("SELECT id, module, teil FROM question_pools WHERE id = ?", [$id]);
            if (!$question) {
                SecurityHeaders::jsonResponse(['error' => 'Question not found'], 404);
            }
            
            // Check if question has been used
            $usageCount = $db->selectOne("SELECT COUNT(*) as count FROM user_answers WHERE question_id = ?", [$id]);
            if ($usageCount['count'] > 0) {
                // Soft delete - just mark as inactive
                $db->update('question_pools', ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
                $action = 'question_deactivated';
            } else {
                // Hard delete
                $db->delete('question_pools', 'id = ?', [$id]);
                $action = 'question_deleted';
            }
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => $action,
                'entity_type' => 'question',
                'entity_id' => $id,
                'old_values' => json_encode(['module' => $question['module'], 'teil' => $question['teil']]),
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'message' => $action === 'question_deactivated' 
                    ? 'Question deactivated (has usage history)' 
                    : 'Question deleted successfully'
            ]);
            break;
            
        default:
            SecurityHeaders::jsonResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log('Admin questions API error: ' . $e->getMessage());
    SecurityHeaders::jsonResponse(['error' => 'Internal server error'], 500);
}
