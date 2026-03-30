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
$type = InputValidator::string($_GET['type'] ?? 'writing', 20);

try {
    $db = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? InputValidator::int($_GET['id']) : null;
            
            if ($id) {
                // Get specific submission with details
                if ($type === 'writing') {
                    $submission = $db->selectOne("
                        SELECT ws.*, u.email, u.display_name as user_name
                        FROM writing_submissions ws
                        JOIN users u ON ws.user_id = u.id
                        WHERE ws.id = ?
                    ", [$id]);
                    
                    if (!$submission) {
                        SecurityHeaders::jsonResponse(['error' => 'Submission not found'], 404);
                    }
                    
                    // Parse JSON fields
                    $submission['ai_feedback'] = json_decode($submission['ai_feedback'] ?? 'null', true);
                    $submission['final_feedback'] = json_decode($submission['final_feedback'] ?? 'null', true);
                    
                } else {
                    $submission = $db->selectOne("
                        SELECT ss.*, u.email, u.display_name as user_name
                        FROM speaking_submissions ss
                        JOIN users u ON ss.user_id = u.id
                        WHERE ss.id = ?
                    ", [$id]);
                    
                    if (!$submission) {
                        SecurityHeaders::jsonResponse(['error' => 'Submission not found'], 404);
                    }
                    
                    $submission['ai_analysis'] = json_decode($submission['ai_analysis'] ?? 'null', true);
                }
                
                SecurityHeaders::jsonResponse(['submission' => $submission, 'type' => $type]);
            }
            
            // List submissions
            $page = max(1, InputValidator::int($_GET['page'] ?? 1) ?: 1);
            $perPage = min(50, max(10, InputValidator::int($_GET['per_page'] ?? 20) ?: 20));
            $offset = ($page - 1) * $perPage;
            
            $status = InputValidator::string($_GET['status'] ?? '', 20);
            $teil = InputValidator::int($_GET['teil'] ?? null);
            
            if ($type === 'writing') {
                $where = ['1=1'];
                $params = [];
                
                if ($status) {
                    $where[] = 'ws.status = ?';
                    $params[] = $status;
                }
                if ($teil) {
                    $where[] = 'ws.teil = ?';
                    $params[] = $teil;
                }
                
                $whereClause = implode(' AND ', $where);
                
                $countResult = $db->selectOne("
                    SELECT COUNT(*) as total 
                    FROM writing_submissions ws
                    WHERE $whereClause
                ", $params);
                $total = (int)$countResult['total'];
                
                $submissions = $db->selectAll("
                    SELECT ws.id, ws.user_id, ws.task_type, ws.teil, ws.status, 
                           ws.word_count, ws.ai_score, ws.final_score, 
                           ws.submitted_at, ws.reviewed_at,
                           u.display_name as user_name, u.email
                    FROM writing_submissions ws
                    JOIN users u ON ws.user_id = u.id
                    WHERE $whereClause
                    ORDER BY 
                        CASE ws.status 
                            WHEN 'pending' THEN 1 
                            WHEN 'ai_reviewed' THEN 2 
                            ELSE 3 
                        END,
                        ws.submitted_at DESC
                    LIMIT ? OFFSET ?
                ", array_merge($params, [$perPage, $offset]));
                
            } else {
                // Speaking submissions
                $where = ['1=1'];
                $params = [];
                
                if ($status) {
                    $where[] = 'ss.status = ?';
                    $params[] = $status;
                }
                if ($teil) {
                    $where[] = 'ss.teil = ?';
                    $params[] = $teil;
                }
                
                $whereClause = implode(' AND ', $where);
                
                $countResult = $db->selectOne("
                    SELECT COUNT(*) as total 
                    FROM speaking_submissions ss
                    WHERE $whereClause
                ", $params);
                $total = (int)$countResult['total'];
                
                $submissions = $db->selectAll("
                    SELECT ss.id, ss.user_id, ss.teil, ss.status, 
                           ss.duration_seconds, ss.overall_score,
                           ss.submitted_at,
                           u.display_name as user_name, u.email
                    FROM speaking_submissions ss
                    JOIN users u ON ss.user_id = u.id
                    WHERE $whereClause
                    ORDER BY 
                        CASE ss.status 
                            WHEN 'pending' THEN 1 
                            WHEN 'ai_processing' THEN 2 
                            ELSE 3 
                        END,
                        ss.submitted_at DESC
                    LIMIT ? OFFSET ?
                ", array_merge($params, [$perPage, $offset]));
            }
            
            // Get status counts for filter
            if ($type === 'writing') {
                $statusCounts = $db->selectAll("
                    SELECT status, COUNT(*) as count 
                    FROM writing_submissions 
                    GROUP BY status
                ");
            } else {
                $statusCounts = $db->selectAll("
                    SELECT status, COUNT(*) as count 
                    FROM speaking_submissions 
                    GROUP BY status
                ");
            }
            
            SecurityHeaders::jsonResponse([
                'submissions' => $submissions,
                'type' => $type,
                'status_counts' => array_column($statusCounts, 'count', 'status'),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'PUT':
            // Update/review submission
            $input = InputValidator::getJsonInput();
            if (!$input || empty($input['id'])) {
                SecurityHeaders::jsonResponse(['error' => 'Submission ID required'], 400);
            }
            
            $id = InputValidator::int($input['id']);
            $action = $input['action'] ?? 'review'; // review, approve, reject
            
            if ($type === 'writing') {
                $submission = $db->selectOne("SELECT * FROM writing_submissions WHERE id = ?", [$id]);
                if (!$submission) {
                    SecurityHeaders::jsonResponse(['error' => 'Submission not found'], 404);
                }
                
                $updates = [];
                
                if (isset($input['final_score'])) {
                    $updates['final_score'] = InputValidator::int($input['final_score'], 0, 100);
                }
                
                if (isset($input['final_feedback'])) {
                    $updates['final_feedback'] = json_encode($input['final_feedback']);
                }
                
                if (isset($input['admin_feedback'])) {
                    $updates['admin_feedback'] = InputValidator::text($input['admin_feedback'], 5000);
                }
                
                if ($action === 'approve') {
                    $updates['status'] = 'approved';
                    $updates['reviewed_at'] = date('Y-m-d H:i:s');
                } elseif ($action === 'reject') {
                    $updates['status'] = 'rejected';
                    $updates['reviewed_at'] = date('Y-m-d H:i:s');
                } elseif ($action === 'review') {
                    $updates['status'] = 'admin_reviewing';
                }
                
                if (empty($updates)) {
                    SecurityHeaders::jsonResponse(['error' => 'No fields to update'], 400);
                }
                
                $db->update('writing_submissions', $updates, 'id = ?', [$id]);
                
            } else {
                // Speaking submission
                $submission = $db->selectOne("SELECT * FROM speaking_submissions WHERE id = ?", [$id]);
                if (!$submission) {
                    SecurityHeaders::jsonResponse(['error' => 'Submission not found'], 404);
                }
                
                $updates = [];
                
                if (isset($input['pronunciation_score'])) {
                    $updates['pronunciation_score'] = InputValidator::int($input['pronunciation_score'], 0, 100);
                }
                if (isset($input['grammar_score'])) {
                    $updates['grammar_score'] = InputValidator::int($input['grammar_score'], 0, 100);
                }
                if (isset($input['fluency_score'])) {
                    $updates['fluency_score'] = InputValidator::int($input['fluency_score'], 0, 100);
                }
                if (isset($input['vocabulary_score'])) {
                    $updates['vocabulary_score'] = InputValidator::int($input['vocabulary_score'], 0, 100);
                }
                if (isset($input['overall_score'])) {
                    $updates['overall_score'] = InputValidator::int($input['overall_score'], 0, 100);
                }
                if (isset($input['analysis'])) {
                    $updates['ai_analysis'] = json_encode($input['analysis']);
                }
                
                if ($action === 'approve') {
                    $updates['status'] = 'approved';
                } elseif ($action === 'reject') {
                    $updates['status'] = 'rejected';
                }
                
                if (empty($updates)) {
                    SecurityHeaders::jsonResponse(['error' => 'No fields to update'], 400);
                }
                
                $db->update('speaking_submissions', $updates, 'id = ?', [$id]);
            }
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => 'submission_reviewed',
                'entity_type' => $type . '_submission',
                'entity_id' => $id,
                'new_values' => json_encode(['action' => $action, 'status' => $updates['status'] ?? 'updated']),
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'message' => 'Submission updated successfully'
            ]);
            break;
            
        case 'DELETE':
            // Delete submission
            $id = InputValidator::int($_GET['id'] ?? null);
            if (!$id) {
                SecurityHeaders::jsonResponse(['error' => 'Submission ID required'], 400);
            }
            
            if ($type === 'writing') {
                $submission = $db->selectOne("SELECT id FROM writing_submissions WHERE id = ?", [$id]);
                if (!$submission) {
                    SecurityHeaders::jsonResponse(['error' => 'Submission not found'], 404);
                }
                $db->delete('writing_submissions', 'id = ?', [$id]);
            } else {
                $submission = $db->selectOne("SELECT id, audio_path FROM speaking_submissions WHERE id = ?", [$id]);
                if (!$submission) {
                    SecurityHeaders::jsonResponse(['error' => 'Submission not found'], 404);
                }
                // Delete audio file if exists
                if (!empty($submission['audio_path']) && file_exists(__DIR__ . '/../../' . $submission['audio_path'])) {
                    unlink(__DIR__ . '/../../' . $submission['audio_path']);
                }
                $db->delete('speaking_submissions', 'id = ?', [$id]);
            }
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => 'submission_deleted',
                'entity_type' => $type . '_submission',
                'entity_id' => $id,
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'message' => 'Submission deleted'
            ]);
            break;
            
        default:
            SecurityHeaders::jsonResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log('Admin submissions API error: ' . $e->getMessage());
    SecurityHeaders::jsonResponse(['error' => 'Internal server error'], 500);
}
