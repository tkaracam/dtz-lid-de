<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';
require_once __DIR__ . '/../../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../../src/Security/InputValidator.php';

use DTZ\Database\Database;
use DTZ\Auth\JWT;
use DTZ\Security\SecurityHeaders;
use DTZ\Security\InputValidator;

// Set security headers
SecurityHeaders::set();
SecurityHeaders::setCors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    SecurityHeaders::jsonResponse(['error' => 'Nur POST erlaubt'], 405);
}

// Rate limiting by IP
$clientIp = InputValidator::getClientIp();
if (!InputValidator::rateLimit('login_' . $clientIp, 5, 60)) {
    SecurityHeaders::jsonResponse(['error' => 'Zu viele Anmeldeversuche. Bitte warten Sie 60 Sekunden.'], 429);
}

// Get and validate input
$input = InputValidator::getJsonInput();
if (!$input) {
    SecurityHeaders::jsonResponse(['error' => 'Ungültige Eingabe'], 400);
}

$email = InputValidator::email($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || empty($password)) {
    SecurityHeaders::jsonResponse(['error' => 'E-Mail und Passwort erforderlich'], 400);
}

// Check password length
if (strlen($password) > 128) {
    SecurityHeaders::jsonResponse(['error' => 'Ungültige Anmeldedaten'], 400);
}

try {
    $db = Database::getInstance();
    
    $user = $db->selectOne(
        "SELECT id, email, display_name as name, password_hash, level, role FROM users WHERE email = ? AND is_active = TRUE",
        [$email]
    );
    
    // Use constant-time comparison to prevent timing attacks
    $passwordValid = false;
    if ($user && !empty($user['password_hash'])) {
        $passwordValid = password_verify($password, $user['password_hash']);
    }
    
    if (!$passwordValid) {
        // Log failed attempt (in production)
        SecurityHeaders::jsonResponse(['error' => 'Ungültige Anmeldedaten'], 401);
    }
    
    // Generate JWT token
    $jwtSecret = $_ENV['JWT_SECRET'] ?? null;
    if (!$jwtSecret) {
        $secretFile = __DIR__ . '/../../.jwt_secret';
        if (file_exists($secretFile)) {
            $jwtSecret = trim(file_get_contents($secretFile));
        } else {
            $jwtSecret = 'dtz-learning-secret-key-change-in-production';
        }
    }
    
    $jwt = new JWT($jwtSecret);
    $token = $jwt->generate([
        'sub' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);
    
    SecurityHeaders::jsonResponse([
        'success' => true,
        'access_token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'level' => $user['level'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    // Log error securely (don't expose details)
    error_log('Login error: ' . $e->getMessage());
    SecurityHeaders::jsonResponse(['error' => 'Anmeldefehler'], 500);
}
