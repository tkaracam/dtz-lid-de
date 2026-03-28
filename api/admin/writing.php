<?php
declare(strict_types=1);

// Include auth check
require_once __DIR__ . '/auth.php';

use DTZ\Database\Database;

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

try {
    switch ($method) {
        case 'GET':
            // List submissions for review
            $status = $_GET['status'] ?? 'ai_reviewed';
            $page = (int) ($_GET['page'] ?? 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            
            $submissions = $db->select(
                "SELECT ws.*, u.display_name, u.email 
                 FROM writing_submissions ws
                 JOIN users u ON ws.user_id = u.id
                 WHERE ws.status = ?
                 ORDER BY ws.submitted_at DESC
                 LIMIT ? OFFSET ?",
                [$status, $perPage, $offset]
            );
            
            // Decode JSON feedback for display
            foreach ($submissions as &$sub) {
                $sub['ai_feedback'] = json_decode($sub['ai_feedback'] ?? '{}', true);
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'submissions' => $submissions,
                'pending_count' => count($submissions)
            ]);
            break;
            
        case 'POST':
            // Admin approves/reviews submission
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['submission_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Submission ID required']);
                exit;
            }
            
            $submissionId = $input['submission_id'];
            $action = $input['action'] ?? 'approve'; // 'approve' or 'reject'
            $adminFeedback = $input['admin_feedback'] ?? null;
            $adminScore = $input['admin_score'] ?? null;
            $adminComment = $input['admin_comment'] ?? '';
            
            // Get current AI feedback
            $submission = $db->selectOne(
                "SELECT ai_feedback FROM writing_submissions WHERE id = ?",
                [$submissionId]
            );
            
            if (!$submission) {
                http_response_code(404);
                echo json_encode(['error' => 'Submission not found']);
                exit;
            }
            
            $aiFeedback = json_decode($submission['ai_feedback'] ?? '{}', true);
            
            // Merge AI and admin feedback for final
            $finalFeedback = $aiFeedback;
            if ($adminFeedback) {
                $finalFeedback = array_merge($aiFeedback, $adminFeedback);
            }
            
            $finalScore = $adminScore ?? ($aiFeedback['overallScore'] ?? 0);
            
            // Update submission
            $db->update('writing_submissions', [
                'admin_id' => $adminUser['id'],
                'admin_feedback' => json_encode($adminFeedback),
                'admin_comment' => $adminComment,
                'admin_score' => $adminScore,
                'final_feedback' => json_encode($finalFeedback),
                'final_score' => $finalScore,
                'status' => $action === 'approve' ? 'approved' : 'rejected',
                'admin_reviewed_at' => date('Y-m-d H:i:s'),
                'approved_at' => $action === 'approve' ? date('Y-m-d H:i:s') : null
            ], 'id = ?', [$submissionId]);
            
            // Log action
            $db->insert('admin_audit_log', [
                'admin_id' => $adminUser['id'],
                'action' => 'writing_' . $action . 'd',
                'entity_type' => 'writing_submission',
                'entity_id' => $submissionId,
                'new_values' => json_encode(['status' => $action])
            ]);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => $action === 'approve' ? 'Freigegeben' : 'Abgelehnt'
            ]);
            break;
            
        case 'PUT':
            // Update submission (edit AI feedback)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['submission_id']) || empty($input['ai_feedback'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            $db->update('writing_submissions', [
                'ai_feedback' => json_encode($input['ai_feedback']),
                'admin_id' => $adminUser['id'],
                'admin_reviewed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$input['submission_id']]);
            
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Aktualisiert']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log('Admin writing error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
