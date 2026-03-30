<?php
declare(strict_types=1);

namespace DTZ\Security;

class InputValidator {
    
    /**
     * Rate limiting storage (in production, use Redis or database)
     */
    private static array $rateLimitCache = [];
    
    /**
     * Validate and sanitize email
     */
    public static function email(string $email): ?string {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return $email;
    }
    
    /**
     * Validate password strength
     */
    public static function password(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Mindestens 8 Zeichen';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Mindestens ein Großbuchstabe';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Mindestens ein Kleinbuchstabe';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Mindestens eine Zahl';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Mindestens ein Sonderzeichen';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize string input
     */
    public static function string(string $input, int $maxLength = 255): string {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Strip HTML tags
        $input = strip_tags($input);
        
        // Limit length
        $input = substr($input, 0, $maxLength);
        
        // Trim whitespace
        return trim($input);
    }
    
    /**
     * Sanitize text (allows some formatting)
     */
    public static function text(string $input, int $maxLength = 5000): string {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove script tags
        $input = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $input);
        
        // Remove event handlers
        $input = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/is', '', $input);
        
        // Remove javascript: URLs
        $input = preg_replace('/javascript:/i', '', $input);
        
        // Limit length
        $input = substr($input, 0, $maxLength);
        
        return trim($input);
    }
    
    /**
     * Validate integer
     */
    public static function int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false || $int < $min || $int > $max) {
            return null;
        }
        return $int;
    }
    
    /**
     * Validate UUID format
     */
    public static function uuid(string $uuid): bool {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
    
    /**
     * Check for SQL injection patterns
     */
    public static function hasSqlInjection(string $input): bool {
        $patterns = [
            '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
            '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|EXEC|EXECUTE|SCRIPT)\b/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for XSS patterns
     */
    public static function hasXss(string $input): bool {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i,
            '/<object/i,
            '/<embed/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Rate limiting check
     */
    public static function rateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool {
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        // Clean old entries
        foreach (self::$rateLimitCache as $k => $entries) {
            self::$rateLimitCache[$k] = array_filter($entries, fn($t) => $t > $windowStart);
        }
        
        // Check current key
        if (!isset(self::$rateLimitCache[$key])) {
            self::$rateLimitCache[$key] = [];
        }
        
        if (count(self::$rateLimitCache[$key]) >= $maxAttempts) {
            return false; // Rate limit exceeded
        }
        
        self::$rateLimitCache[$key][] = $now;
        return true;
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIp(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(?string $token): bool {
        if (empty($token)) {
            return false;
        }
        
        // In production, validate against session or database
        // This is a simplified version
        return strlen($token) === 64 && ctype_xdigit($token);
    }
    
    /**
     * Get JSON input safely
     */
    public static function getJsonInput(): ?array {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return null;
        }
        
        // Check for JSON hijacking
        $input = ltrim($input);
        if (strpos($input, '{') !== 0 && strpos($input, '[') !== 0) {
            return null;
        }
        
        try {
            $data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : null;
        } catch (\JsonException $e) {
            return null;
        }
    }
}
