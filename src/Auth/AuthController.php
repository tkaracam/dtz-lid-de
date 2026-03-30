<?php
declare(strict_types=1);

namespace DTZ\Auth;

use DTZ\Database\Database;

require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/../Database/Database.php';

class AuthController {
    private JWT $jwt;
    private Database $db;
    
    public function __construct() {
        $secret = $_ENV['JWT_SECRET'] ?? $this->getFallbackSecret();
        $this->jwt = new JWT($secret, 86400 * 7); // 7 days
        $this->db = Database::getInstance();
    }
    
    private function getFallbackSecret(): string {
        $secretFile = __DIR__ . '/../../.jwt_secret';
        if (file_exists($secretFile)) {
            return trim(file_get_contents($secretFile));
        }
        return 'dtz-learning-secret-key-change-in-production';
    }
    
    public function authenticate(): ?array {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
        
        if (empty($token)) {
            return null;
        }
        
        $payload = $this->jwt->verify($token);
        
        if (!$payload) {
            return null;
        }
        
        $user = $this->db->selectOne(
            "SELECT id, email, display_name as name, level, role FROM users WHERE id = ? AND is_active = TRUE",
            [$payload['sub']]
        );
        
        return $user;
    }
    
    public function me(string $token): ?array {
        $payload = $this->jwt->verify($token);
        
        if (!$payload) {
            return null;
        }
        
        $user = $this->db->selectOne(
            "SELECT id, email, display_name as name, level, role FROM users WHERE id = ? AND is_active = TRUE",
            [$payload['sub']]
        );
        
        return $user;
    }
    
    public function generateToken(array $user): string {
        return $this->jwt->generate([
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user'
        ]);
    }
}
