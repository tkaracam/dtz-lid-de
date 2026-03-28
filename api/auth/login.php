<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';

use DTZ\Database\Database;
use DTZ\Auth\JWT;

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

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'E-Mail und Passwort erforderlich']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $user = $db->selectOne(
        "SELECT id, email, display_name as name, password_hash, level, role FROM users WHERE email = ? AND is_active = TRUE",
        [$email]
    );
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Ungültige Anmeldedaten']);
        exit;
    }
    
    // Generate JWT token
    $jwt = new JWT($_ENV['JWT_SECRET'] ?? 'dtz-learning-secret-key-change-in-production');
    $token = $jwt->generate([
        'sub' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);
    
    echo json_encode([
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
    http_response_code(500);
    echo json_encode(['error' => 'Anmeldefehler']);
}
