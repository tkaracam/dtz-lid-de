<?php
/**
 * Health check endpoint for connectivity monitoring
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Basic health status
$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'version' => '1.0.0'
];

// Check database connection
try {
    require_once __DIR__ . '/../src/Database/Database.php';
    $db = \DTZ\Database\Database::getInstance();
    $health['database'] = 'connected';
} catch (Exception $e) {
    $health['database'] = 'error';
    $health['status'] = 'degraded';
}

http_response_code(200);
echo json_encode($health);
