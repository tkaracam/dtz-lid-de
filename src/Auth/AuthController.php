<?php
declare(strict_types=1);

namespace DTZ\Auth;

use DTZ\Models\User;
use DTZ\Models\Session;

class AuthController
{
    private JWT $jwt;
    private User $userModel;
    private Session $sessionModel;
    
    // Token TTL in seconds
    private int $accessTokenTtl = 3600;      // 1 hour
    private int $refreshTokenTtl = 2592000;  // 30 days
    
    public function __construct()
    {
        $secret = $_ENV['JWT_SECRET'] ?? $this->generateFallbackSecret();
        $this->jwt = new JWT($secret);
        $this->userModel = new User();
        $this->sessionModel = new Session();
    }
    
    /**
     * Register new user
     */
    public function register(array $data): array
    {
        // Validation
        $errors = $this->validateRegistration($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if email exists
        if ($this->userModel->emailExists($data['email'])) {
            return [
                'success' => false, 
                'errors' => ['email' => 'Diese E-Mail-Adresse wird bereits verwendet']
            ];
        }
        
        // Create user
        $userId = $this->userModel->create([
            'email' => $data['email'],
            'password' => $data['password'],
            'display_name' => $data['display_name'],
            'level' => $data['level'] ?? 'A2',
        ]);
        
        // Create session
        $tokens = $this->createSession($userId);
        
        return [
            'success' => true,
            'message' => 'Registrierung erfolgreich',
            'user' => [
                'id' => $userId,
                'email' => strtolower(trim($data['email'])),
                'display_name' => trim($data['display_name']),
                'level' => $data['level'] ?? 'A2',
                'subscription_status' => 'trialing',
                'trial_days_remaining' => 7,
            ],
            'tokens' => $tokens
        ];
    }
    
    /**
     * Login user
     */
    public function login(array $data): array
    {
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'error' => 'E-Mail und Passwort sind erforderlich'
            ];
        }
        
        // Find user
        $user = $this->userModel->findByEmail($email);
        
        if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            return [
                'success' => false,
                'error' => 'Ungültige E-Mail oder Passwort'
            ];
        }
        
        // Check trial expiration
        if ($user['subscription_status'] === 'trialing' && !$this->userModel->isTrialValid($user)) {
            $this->userModel->updateSubscriptionStatus($user['id'], 'expired');
            $user['subscription_status'] = 'expired';
        }
        
        // Update last activity
        $this->userModel->updateLastActivity($user['id']);
        
        // Create session
        $tokens = $this->createSession($user['id']);
        
        // Calculate trial days
        $trialDays = 0;
        if ($user['subscription_status'] === 'trialing' && $user['trial_ends_at']) {
            $trialDays = max(0, (strtotime($user['trial_ends_at']) - time()) / 86400);
        }
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'display_name' => $user['display_name'],
                'level' => $user['level'],
                'subscription_status' => $user['subscription_status'],
                'trial_days_remaining' => round($trialDays),
                'streak_count' => $user['streak_count'],
                'daily_goal' => $user['daily_goal'],
            ],
            'tokens' => $tokens
        ];
    }
    
    /**
     * Refresh access token
     */
    public function refresh(string $refreshToken): array
    {
        $session = $this->sessionModel->validateRefresh($refreshToken);
        
        if (!$session) {
            return [
                'success' => false,
                'error' => 'Ungültiger Refresh-Token'
            ];
        }
        
        // Invalidate old session
        $this->sessionModel->invalidate($session['session_token']);
        
        // Create new session
        $tokens = $this->createSession($session['user_id']);
        
        return [
            'success' => true,
            'tokens' => $tokens
        ];
    }
    
    /**
     * Logout user
     */
    public function logout(string $token): array
    {
        $this->sessionModel->invalidate($token);
        
        return [
            'success' => true,
            'message' => 'Erfolgreich abgemeldet'
        ];
    }
    
    /**
     * Get current user from token
     */
    public function me(string $token): ?array
    {
        $payload = $this->jwt->decode($token);
        
        if (!$payload) {
            return null;
        }
        
        $user = $this->userModel->findById($payload['sub'] ?? 0);
        
        if (!$user) {
            return null;
        }
        
        // Check and update trial status
        if ($user['subscription_status'] === 'trialing' && !$this->userModel->isTrialValid($user)) {
            $this->userModel->updateSubscriptionStatus($user['id'], 'expired');
            $user['subscription_status'] = 'expired';
        }
        
        // Update session activity
        $this->sessionModel->touch($token);
        
        $trialDays = 0;
        if ($user['subscription_status'] === 'trialing' && $user['trial_ends_at']) {
            $trialDays = max(0, (strtotime($user['trial_ends_at']) - time()) / 86400);
        }
        
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'display_name' => $user['display_name'],
            'level' => $user['level'],
            'subscription_status' => $user['subscription_status'],
            'trial_days_remaining' => round($trialDays),
            'streak_count' => $user['streak_count'],
            'daily_goal' => $user['daily_goal'],
        ];
    }
    
    /**
     * Create new session with tokens
     */
    private function createSession(int $userId): array
    {
        $accessToken = $this->jwt->generate(
            ['sub' => $userId, 'type' => 'access'],
            $this->accessTokenTtl
        );
        
        $refreshToken = $this->jwt->generateRefreshToken($userId);
        
        $this->sessionModel->create(
            $userId,
            $accessToken,
            $refreshToken,
            $this->refreshTokenTtl
        );
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTokenTtl,
            'token_type' => 'Bearer'
        ];
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];
        
        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'E-Mail ist erforderlich';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ungültige E-Mail-Adresse';
        } elseif (strlen($data['email']) > 255) {
            $errors['email'] = 'E-Mail darf maximal 255 Zeichen haben';
        }
        
        // Password
        if (empty($data['password'])) {
            $errors['password'] = 'Passwort ist erforderlich';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Passwort muss mindestens 8 Zeichen haben';
        } elseif (!preg_match('/[A-Z]/', $data['password'])) {
            $errors['password'] = 'Passwort muss mindestens einen Großbuchstaben enthalten';
        } elseif (!preg_match('/[a-z]/', $data['password'])) {
            $errors['password'] = 'Passwort muss mindestens einen Kleinbuchstaben enthalten';
        } elseif (!preg_match('/[0-9]/', $data['password'])) {
            $errors['password'] = 'Passwort muss mindestens eine Zahl enthalten';
        }
        
        // Display name
        if (empty($data['display_name'])) {
            $errors['display_name'] = 'Name ist erforderlich';
        } elseif (strlen($data['display_name']) < 2) {
            $errors['display_name'] = 'Name muss mindestens 2 Zeichen haben';
        } elseif (strlen($data['display_name']) > 100) {
            $errors['display_name'] = 'Name darf maximal 100 Zeichen haben';
        }
        
        // Level
        if (!empty($data['level']) && !in_array($data['level'], ['A1', 'A2', 'B1', 'B2'])) {
            $errors['level'] = 'Ungültiges Sprachniveau';
        }
        
        return $errors;
    }
    
    /**
     * Generate fallback secret (development only)
     */
    private function generateFallbackSecret(): string
    {
        $secretFile = __DIR__ . '/../../.jwt_secret';
        
        if (file_exists($secretFile)) {
            return file_get_contents($secretFile);
        }
        
        $secret = bin2hex(random_bytes(32));
        file_put_contents($secretFile, $secret);
        chmod($secretFile, 0600);
        
        return $secret;
    }
}
