<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Models/Session.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';

use DTZ\Auth\AuthController;

// CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['refresh_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Refresh-Token ist erforderlich']);
    exit;
}

// Process refresh
try {
    $auth = new AuthController();
    $result = $auth->refresh($input['refresh_token']);
    
    if (!$result['success']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'tokens' => $result['tokens']
    ]);
    
} catch (Exception $e) {
    error_log('Refresh error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}
