<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class Session
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new session
     */
    public function create(int $userId, string $token, string $refreshToken, int $ttl = 86400): void
    {
        $this->db->insert('user_sessions', [
            'user_id' => $userId,
            'session_token' => $token,
            'refresh_token' => $refreshToken,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
            'ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'device_info' => json_encode([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'timestamp' => date('c')
            ])
        ]);
    }
    
    /**
     * Validate session token
     */
    public function validate(string $token): ?array
    {
        return $this->db->selectOne(
            "SELECT s.*, u.email, u.display_name, u.subscription_status, u.level 
             FROM user_sessions s
             JOIN users u ON s.user_id = u.id
             WHERE s.session_token = ? 
             AND s.expires_at > datetime('now')
             AND s.is_valid = 1
             LIMIT 1",
            [$token]
        );
    }
    
    /**
     * Validate refresh token
     */
    public function validateRefresh(string $refreshToken): ?array
    {
        return $this->db->selectOne(
            "SELECT s.*, u.id as user_id, u.email 
             FROM user_sessions s
             JOIN users u ON s.user_id = u.id
             WHERE s.refresh_token = ? 
             AND s.is_valid = 1
             LIMIT 1",
            [$refreshToken]
        );
    }
    
    /**
     * Invalidate session
     */
    public function invalidate(string $token): void
    {
        $this->db->update('user_sessions',
            ['is_valid' => 0],
            'session_token = ?',
            [$token]
        );
    }
    
    /**
     * Invalidate all user sessions
     */
    public function invalidateAll(int $userId): void
    {
        $this->db->update('user_sessions',
            ['is_valid' => 0],
            'user_id = ?',
            [$userId]
        );
    }
    
    /**
     * Update last activity
     */
    public function touch(string $token): void
    {
        $this->db->update('user_sessions',
            ['last_activity_at' => date('Y-m-d H:i:s')],
            'session_token = ?',
            [$token]
        );
    }
    
    /**
     * Clean expired sessions
     */
    public function cleanup(): int
    {
        $stmt = $this->db->execute(
            "DELETE FROM user_sessions WHERE expires_at < datetime('now') OR is_valid = 0"
        );
        return $stmt->rowCount();
    }
}
