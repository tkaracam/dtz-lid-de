<?php
/**
 * DTZ Learning API Router
 * Simple file-based routing
 */

declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Remove query string
$uri = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix
$uri = preg_replace('#^/api/?#', '', $uri);

// Route to file
$parts = explode('/', trim($uri, '/'));

if (count($parts) >= 2) {
    $folder = $parts[0];
    $file = $parts[1];
    
    $targetFile = __DIR__ . '/' . $folder . '/' . $file . '.php';
    
    if (file_exists($targetFile)) {
        require $targetFile;
        exit;
    }
}

// Default: return API info
header('Content-Type: application/json');
echo json_encode([
    'name' => 'DTZ Learning API',
    'version' => '1.0.0',
    'endpoints' => [
        'POST /auth/login',
        'POST /auth/register',
        'GET /auth/me',
        'GET /user/stats',
        'POST /modelltest/start',
        'GET /modelltest/status',
        'POST /modelltest/answer',
        'GET /modelltest/result'
    ]
]);
