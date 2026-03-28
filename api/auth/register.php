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

// Rate limiting (simple)
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/dtz_rate_' . md5($ip) . '.json';
$rateLimit = 5; // requests per minute

$rateData = ['count' => 0, 'time' => time()];
if (file_exists($rateFile)) {
    $rateData = json_decode(file_get_contents($rateFile), true);
}

if (time() - $rateData['time'] > 60) {
    $rateData = ['count' => 0, 'time' => time()];
}

$rateData['count']++;
file_put_contents($rateFile, json_encode($rateData));

if ($rateData['count'] > $rateLimit) {
    http_response_code(429);
    echo json_encode(['error' => 'Zu viele Anfragen. Bitte warten Sie einen Moment.']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiges JSON']);
    exit;
}

// Process registration
try {
    $auth = new AuthController();
    $result = $auth->register($input);
    
    if (!$result['success']) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $result['errors']
        ]);
        exit;
    }
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'user' => $result['user'],
        'tokens' => $result['tokens']
    ]);
    
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}
