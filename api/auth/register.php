<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$name = $input['name'] ?? '';

if (empty($email) || empty($password) || empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Alle Felder sind erforderlich']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwort muss mindestens 8 Zeichen lang sein']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if email exists
    $existing = $db->selectOne(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    if ($existing) {
        http_response_code(409);
        echo json_encode(['error' => 'E-Mail bereits registriert']);
        exit;
    }
    
    // Create user
    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
    
    $userId = $db->insert('users', [
        'email' => $email,
        'display_name' => $name,
        'name' => $name,
        'password_hash' => $passwordHash,
        'subscription_status' => 'trialing',
        'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registrierung erfolgreich',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Registrierung fehlgeschlagen']);
}
