<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class User
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM users WHERE email = ? LIMIT 1",
            [strtolower(trim($email))]
        );
    }
    
    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$id]
        );
    }
    
    /**
     * Create new user with 7-day trial
     */
    public function create(array $data): int
    {
        $trialDays = 7;
        $trialEnds = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));
        
        return $this->db->insert('users', [
            'email' => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_ARGON2ID),
            'display_name' => trim($data['display_name']),
            'level' => $data['level'] ?? 'A2',
            'subscription_status' => 'trialing',
            'trial_ends_at' => $trialEnds,
            'daily_goal' => $data['daily_goal'] ?? 10,
        ]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Update last activity
     */
    public function updateLastActivity(int $userId): void
    {
        $this->db->update('users', 
            ['last_activity_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userId]
        );
    }
    
    /**
     * Check if trial is still valid
     */
    public function isTrialValid(array $user): bool
    {
        if ($user['subscription_status'] !== 'trialing') {
            return false;
        }
        
        return strtotime($user['trial_ends_at']) > time();
    }
    
    /**
     * Update subscription status
     */
    public function updateSubscriptionStatus(int $userId, string $status): void
    {
        $this->db->update('users',
            ['subscription_status' => $status],
            'id = ?',
            [$userId]
        );
    }
    
    /**
     * Increment streak
     */
    public function incrementStreak(int $userId): void
    {
        $this->db->execute(
            "UPDATE users SET streak_count = streak_count + 1 WHERE id = ?",
            [$userId]
        );
    }
    
    /**
     * Reset streak
     */
    public function resetStreak(int $userId): void
    {
        $this->db->update('users',
            ['streak_count' => 0],
            'id = ?',
            [$userId]
        );
    }
    
    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->selectOne(
            "SELECT 1 FROM users WHERE email = ? LIMIT 1",
            [strtolower(trim($email))]
        );
        return $result !== null;
    }
}
