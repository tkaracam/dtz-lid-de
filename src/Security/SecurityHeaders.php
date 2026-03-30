<?php
declare(strict_types=1);

namespace DTZ\Security;

class SecurityHeaders {
    
    /**
     * Set security headers for all responses
     */
    public static function set(): void {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://js.stripe.com; ";
        $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; ";
        $csp .= "font-src 'self' https://fonts.gstatic.com; ";
        $csp .= "img-src 'self' data: https: blob:; ";
        $csp .= "connect-src 'self' https://api.stripe.com; ";
        $csp .= "frame-src https://js.stripe.com https://hooks.stripe.com; ";
        $csp .= "base-uri 'self'; ";
        $csp .= "form-action 'self';";
        header("Content-Security-Policy: $csp");
        
        // Strict Transport Security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
    
    /**
     * Set CORS headers for API endpoints
     */
    public static function setCors(array $allowedOrigins = []): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Default allowed origins
        $defaultOrigins = [
            'http://localhost:8080',
            'https://localhost:8080',
        ];
        
        $allowed = array_merge($defaultOrigins, $allowedOrigins);
        
        if (in_array($origin, $allowed, true)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    
    /**
     * Sanitize JSON output
     */
    public static function jsonResponse(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        // Prevent JSON hijacking
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}
