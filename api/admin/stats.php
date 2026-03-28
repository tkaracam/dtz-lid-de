<?php
declare(strict_types=1);

// This includes auth check
require_once __DIR__ . '/auth.php';

require_once __DIR__ . '/../../src/Models/Admin.php';

use DTZ\Models\Admin;

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur GET erlaubt']);
    exit;
}

try {
    $adminModel = new Admin();
    $stats = $adminModel->getPlatformStats();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log('Admin stats error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}
