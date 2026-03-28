<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class Admin
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(int $userId): bool
    {
        $user = $this->db->selectOne(
            "SELECT is_admin, is_super_admin FROM users WHERE id = ?",
            [$userId]
        );
        
        return $user && ($user['is_admin'] || $user['is_super_admin']);
    }
    
    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(int $userId): bool
    {
        $user = $this->db->selectOne(
            "SELECT is_super_admin FROM users WHERE id = ?",
            [$userId]
        );
        
        return $user && $user['is_super_admin'];
    }
    
    /**
     * Get platform statistics
     */
    public function getPlatformStats(): array
    {
        $stats = [];
        
        // User counts
        $userStats = $this->db->selectOne("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN subscription_status = 'premium' THEN 1 ELSE 0 END) as premium_users,
                SUM(CASE WHEN subscription_status = 'trialing' THEN 1 ELSE 0 END) as trial_users,
                SUM(CASE WHEN subscription_status = 'free' THEN 1 ELSE 0 END) as free_users,
                SUM(CASE WHEN DATE(created_at) = DATE('now') THEN 1 ELSE 0 END) as new_today
            FROM users
        ");
        $stats['users'] = $userStats;
        
        // Question counts
        $questionStats = $this->db->selectOne("
            SELECT 
                COUNT(*) as total_questions,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_questions,
                SUM(CASE WHEN module = 'lesen' THEN 1 ELSE 0 END) as lesen_count,
                SUM(CASE WHEN module = 'hoeren' THEN 1 ELSE 0 END) as hoeren_count,
                SUM(CASE WHEN module = 'schreiben' THEN 1 ELSE 0 END) as schreiben_count,
                SUM(CASE WHEN module = 'sprechen' THEN 1 ELSE 0 END) as sprechen_count,
                SUM(CASE WHEN module = 'lid' THEN 1 ELSE 0 END) as lid_count
            FROM question_pools
        ");
        $stats['questions'] = $questionStats;
        
        // Answer stats
        $answerStats = $this->db->selectOne("
            SELECT 
                COUNT(*) as total_answers,
                SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) as correct_answers,
                SUM(CASE WHEN DATE(created_at) = DATE('now') THEN 1 ELSE 0 END) as answers_today
            FROM user_answers
        ");
        $stats['answers'] = $answerStats;
        
        // Revenue (approximate)
        $revenueStats = $this->db->selectOne("
            SELECT 
                SUM(CASE WHEN status = 'succeeded' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = 'succeeded' AND DATE(created_at) >= DATE('now', '-30 days') THEN amount ELSE 0 END) as revenue_30d
            FROM payments
        ");
        $stats['revenue'] = $revenueStats;
        
        return $stats;
    }
    
    /**
     * Get all users with pagination
     */
    public function getUsers(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $whereClause = '';
        if ($search) {
            $whereClause = "WHERE email LIKE ? OR display_name LIKE ?";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $users = $this->db->select("
            SELECT 
                id, email, display_name, level, subscription_status, 
                trial_ends_at, streak_count, last_activity_at, created_at,
                is_admin
            FROM users
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$perPage, $offset]));
        
        // Get total count
        $countResult = $this->db->selectOne("
            SELECT COUNT(*) as total FROM users {$whereClause}
        ", $params);
        
        return [
            'users' => $users,
            'total' => (int) ($countResult['total'] ?? 0),
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil((int) ($countResult['total'] ?? 0) / $perPage)
        ];
    }
    
    /**
     * Update user
     */
    public function updateUser(int $userId, array $data): bool
    {
        $allowedFields = ['subscription_status', 'level', 'daily_goal', 'is_admin'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->db->update('users', $updateData, 'id = ?', [$userId]) > 0;
    }
    
    /**
     * Delete user
     */
    public function deleteUser(int $userId): bool
    {
        return $this->db->delete('users', 'id = ?', [$userId]) > 0;
    }
    
    /**
     * Get recent activity
     */
    public function getRecentActivity(int $limit = 20): array
    {
        return $this->db->select("
            SELECT 
                ua.created_at,
                u.display_name,
                u.email,
                q.module,
                ua.is_correct,
                ua.points_earned
            FROM user_answers ua
            JOIN users u ON ua.user_id = u.id
            JOIN question_pools q ON ua.question_id = q.id
            ORDER BY ua.created_at DESC
            LIMIT ?
        ", [$limit]);
    }
}
