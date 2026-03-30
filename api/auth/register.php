<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../../src/Security/InputValidator.php';

use DTZ\Database\Database;
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
if (!InputValidator::rateLimit('register_' . $clientIp, 3, 3600)) {
    SecurityHeaders::jsonResponse(['error' => 'Zu viele Registrierungsversuche. Bitte später erneut versuchen.'], 429);
}

// Get and validate input
$input = InputValidator::getJsonInput();
if (!$input) {
    SecurityHeaders::jsonResponse(['error' => 'Ungültige Eingabe'], 400);
}

$email = InputValidator::email($input['email'] ?? '');
$password = $input['password'] ?? '';
$name = InputValidator::string($input['name'] ?? '', 100);

if (!$email || empty($password) || empty($name)) {
    SecurityHeaders::jsonResponse(['error' => 'Alle Felder sind erforderlich'], 400);
}

// Validate password strength
$passwordCheck = InputValidator::password($password);
if (!$passwordCheck['valid']) {
    SecurityHeaders::jsonResponse([
        'error' => 'Passwort ist zu schwach',
        'requirements' => $passwordCheck['errors']
    ], 400);
}

// Check for suspicious input
if (InputValidator::hasXss($name)) {
    SecurityHeaders::jsonResponse(['error' => 'Ungültige Eingabe'], 400);
}

try {
    $db = Database::getInstance();
    
    // Check if email exists (case-insensitive)
    $existing = $db->selectOne(
        "SELECT id FROM users WHERE LOWER(email) = LOWER(?)",
        [$email]
    );
    
    if ($existing) {
        SecurityHeaders::jsonResponse(['error' => 'E-Mail bereits registriert'], 409);
    }
    
    // Create user with Argon2id
    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
    
    $userId = $db->insert('users', [
        'email' => $email,
        'display_name' => $name,
        'password_hash' => $passwordHash,
        'subscription_status' => 'trialing',
        'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    SecurityHeaders::jsonResponse([
        'success' => true,
        'message' => 'Registrierung erfolgreich',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    SecurityHeaders::jsonResponse(['error' => 'Registrierung fehlgeschlagen'], 500);
}
