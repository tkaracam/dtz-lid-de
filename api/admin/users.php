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
            // Get single user or list
            $id = isset($_GET['id']) ? InputValidator::int($_GET['id']) : null;
            
            if ($id) {
                // Get specific user
                $user = $db->selectOne("
                    SELECT u.id, u.email, u.display_name as name, u.level, 
                           u.subscription_status, u.trial_ends_at, u.is_active,
                           u.created_at, u.last_activity_at, u.streak_count, u.daily_goal,
                           (SELECT COUNT(*) FROM user_answers WHERE user_id = u.id) as total_answers,
                           (SELECT COUNT(*) FROM writing_submissions WHERE user_id = u.id) as writing_count,
                           (SELECT COUNT(*) FROM speaking_submissions WHERE user_id = u.id) as speaking_count
                    FROM users u
                    WHERE u.id = ?
                ", [$id]);
                
                if (!$user) {
                    SecurityHeaders::jsonResponse(['error' => 'User not found'], 404);
                }
                
                SecurityHeaders::jsonResponse(['user' => $user]);
            }
            
            // List users with filters
            $page = max(1, InputValidator::int($_GET['page'] ?? 1) ?: 1);
            $perPage = min(100, max(10, InputValidator::int($_GET['per_page'] ?? 20) ?: 20));
            $offset = ($page - 1) * $perPage;
            
            $search = InputValidator::string($_GET['search'] ?? '', 100);
            $status = InputValidator::string($_GET['status'] ?? '', 20);
            $level = InputValidator::string($_GET['level'] ?? '', 2);
            
            // Build query
            $where = ['1=1'];
            $params = [];
            
            if ($search) {
                $where[] = '(email LIKE ? OR display_name LIKE ?)';
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($status) {
                $where[] = 'subscription_status = ?';
                $params[] = $status;
            }
            
            if ($level) {
                $where[] = 'level = ?';
                $params[] = $level;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Get total count
            $countResult = $db->selectOne("SELECT COUNT(*) as total FROM users WHERE $whereClause", $params);
            $total = (int)$countResult['total'];
            
            // Get users
            $users = $db->selectAll("
                SELECT id, email, display_name as name, level, subscription_status, 
                       is_active, created_at, last_activity_at, streak_count
                FROM users 
                WHERE $whereClause
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ", array_merge($params, [$perPage, $offset]));
            
            SecurityHeaders::jsonResponse([
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'POST':
            // Create new user
            $input = InputValidator::getJsonInput();
            if (!$input) {
                SecurityHeaders::jsonResponse(['error' => 'Invalid input'], 400);
            }
            
            $email = InputValidator::email($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $name = InputValidator::string($input['name'] ?? '', 100);
            $level = InputValidator::string($input['level'] ?? 'A2', 2);
            $subscription = InputValidator::string($input['subscription_status'] ?? 'free', 20);
            
            if (!$email || !$password || !$name) {
                SecurityHeaders::jsonResponse(['error' => 'Email, password and name are required'], 400);
            }
            
            // Check if email exists
            $existing = $db->selectOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                SecurityHeaders::jsonResponse(['error' => 'Email already exists'], 409);
            }
            
            // Validate password
            $passwordCheck = InputValidator::password($password);
            if (!$passwordCheck['valid']) {
                SecurityHeaders::jsonResponse(['error' => 'Password too weak', 'requirements' => $passwordCheck['errors']], 400);
            }
            
            // Create user
            $userId = $db->insert('users', [
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
                'display_name' => $name,
                'level' => $level,
                'subscription_status' => $subscription,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create progress cache
            $db->insert('user_progress_cache', [
                'user_id' => $userId,
                'current_level' => $level
            ]);
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => 'user_created',
                'entity_type' => 'user',
                'entity_id' => $userId,
                'new_values' => json_encode(['email' => $email, 'name' => $name]),
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'user_id' => $userId,
                'message' => 'User created successfully'
            ], 201);
            break;
            
        case 'PUT':
            // Update user
            $input = InputValidator::getJsonInput();
            if (!$input || empty($input['id'])) {
                SecurityHeaders::jsonResponse(['error' => 'User ID required'], 400);
            }
            
            $id = InputValidator::int($input['id']);
            $user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$id]);
            
            if (!$user) {
                SecurityHeaders::jsonResponse(['error' => 'User not found'], 404);
            }
            
            $updates = [];
            $oldValues = [];
            $newValues = [];
            
            if (isset($input['name'])) {
                $updates['display_name'] = InputValidator::string($input['name'], 100);
                $oldValues['name'] = $user['display_name'];
                $newValues['name'] = $updates['display_name'];
            }
            
            if (isset($input['level'])) {
                $updates['level'] = InputValidator::string($input['level'], 2);
                $oldValues['level'] = $user['level'];
                $newValues['level'] = $updates['level'];
            }
            
            if (isset($input['subscription_status'])) {
                $updates['subscription_status'] = InputValidator::string($input['subscription_status'], 20);
                $oldValues['subscription_status'] = $user['subscription_status'];
                $newValues['subscription_status'] = $updates['subscription_status'];
            }
            
            if (isset($input['is_active'])) {
                $updates['is_active'] = $input['is_active'] ? 1 : 0;
                $oldValues['is_active'] = $user['is_active'];
                $newValues['is_active'] = $updates['is_active'];
            }
            
            if (isset($input['password']) && !empty($input['password'])) {
                $updates['password_hash'] = password_hash($input['password'], PASSWORD_ARGON2ID);
                $newValues['password'] = '[CHANGED]';
            }
            
            if (empty($updates)) {
                SecurityHeaders::jsonResponse(['error' => 'No fields to update'], 400);
            }
            
            $db->update('users', $updates, 'id = ?', [$id]);
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => 'user_updated',
                'entity_type' => 'user',
                'entity_id' => $id,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($newValues),
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
            break;
            
        case 'DELETE':
            // Delete user
            $id = InputValidator::int($_GET['id'] ?? null);
            if (!$id) {
                SecurityHeaders::jsonResponse(['error' => 'User ID required'], 400);
            }
            
            $user = $db->selectOne("SELECT id, email FROM users WHERE id = ?", [$id]);
            if (!$user) {
                SecurityHeaders::jsonResponse(['error' => 'User not found'], 404);
            }
            
            // Prevent deleting self
            if ($id === $adminUser['id']) {
                SecurityHeaders::jsonResponse(['error' => 'Cannot delete yourself'], 400);
            }
            
            $db->delete('users', 'id = ?', [$id]);
            
            // Log action
            $db->insert('audit_logs', [
                'user_id' => $adminUser['id'],
                'action' => 'user_deleted',
                'entity_type' => 'user',
                'entity_id' => $id,
                'old_values' => json_encode(['email' => $user['email']]),
                'ip_address' => InputValidator::getClientIp(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            SecurityHeaders::jsonResponse([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            break;
            
        default:
            SecurityHeaders::jsonResponse(['error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log('Admin users API error: ' . $e->getMessage());
    SecurityHeaders::jsonResponse(['error' => 'Internal server error'], 500);
}
