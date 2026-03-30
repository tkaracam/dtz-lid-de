<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Security/SecurityHeaders.php';

use DTZ\Security\SecurityHeaders;

SecurityHeaders::setCors();

// Accept POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    exit;
}

// Log to file (in production, use proper logging service)
$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0750, true);
}

$logFile = $logDir . '/metrics-' . date('Y-m-d') . '.log';
$logEntry = [
    'timestamp' => date('c'),
    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
    'data' => $input
];

// Rotate logs if too large
if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
    rename($logFile, $logFile . '.' . date('H-i-s'));
}

// Append to log
file_put_contents(
    $logFile, 
    json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", 
    FILE_APPEND | LOCK_EX
);

// Critical errors - alert immediately
if (($input['type'] ?? '') === 'error') {
    // TODO: Send to Slack/Email in production
    error_log('Critical error: ' . json_encode($input['details'] ?? []));
}

http_response_code(204);
