<?php
header('Content-Type: application/json');

$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
}

echo json_encode([
    'server' => $_SERVER,
    'headers' => $headers,
    'raw_auth' => $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT SET'
], JSON_PRETTY_PRINT);
