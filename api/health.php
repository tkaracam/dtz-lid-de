<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Security/SecurityHeaders.php';

use DTZ\Security\SecurityHeaders;

SecurityHeaders::set();

$checks = [];
$healthy = true;

// Database check
try {
    require_once __DIR__ . '/../src/Database/Database.php';
    $db = DTZ\Database\Database::getInstance();
    $db->selectOne("SELECT 1");
    $checks['database'] = 'OK';
} catch (Exception $e) {
    $checks['database'] = 'FAIL: ' . $e->getMessage();
    $healthy = false;
}

// JWT Secret check
$secretFile = __DIR__ . '/../.jwt_secret';
$secret = $_ENV['JWT_SECRET'] ?? null;
if (!$secret && file_exists($secretFile)) {
    $secret = trim(file_get_contents($secretFile));
}
$checks['jwt_secret'] = $secret ? 'OK' : 'FAIL: Secret not found';
if (!$secret) $healthy = false;

// Disk space check
$freeSpace = disk_free_space(__DIR__);
$totalSpace = disk_total_space(__DIR__);
$freePercent = ($freeSpace / $totalSpace) * 100;
$checks['disk_space'] = round($freePercent, 2) . '% free';
if ($freePercent < 10) $healthy = false;

// Memory check
$memoryLimit = ini_get('memory_limit');
$checks['memory_limit'] = $memoryLimit;

// PHP version
$checks['php_version'] = PHP_VERSION;

// Response time
$checks['response_time'] = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's';

// Status
$status = [
    'status' => $healthy ? 'healthy' : 'unhealthy',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'checks' => $checks
];

http_response_code($healthy ? 200 : 503);
header('Content-Type: application/json');
echo json_encode($status, JSON_PRETTY_PRINT);
