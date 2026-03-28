<?php
/**
 * Debug questions API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$debug = [];

// Check database files
$dbPaths = [
    '/var/www/html/database/dtz_production.db',
    '/var/www/html/database/dtz_learning.db',
    __DIR__ . '/../database/dtz_production.db',
    __DIR__ . '/../database/dtz_learning.db',
];

foreach ($dbPaths as $path) {
    $debug['db_paths'][] = [
        'path' => $path,
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'size' => file_exists($path) ? filesize($path) : 0
    ];
}

// Try to connect and query
try {
    $dbPath = null;
    foreach ($dbPaths as $path) {
        if (file_exists($path) && filesize($path) > 0) {
            $dbPath = $path;
            break;
        }
    }
    
    if (!$dbPath) {
        throw new Exception('No database file found');
    }
    
    $debug['selected_db'] = $dbPath;
    
    $pdo = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Check tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    $debug['tables'] = $tables;
    
    // Check question_pools
    if (in_array('question_pools', $tables)) {
        $count = $pdo->query("SELECT COUNT(*) FROM question_pools")->fetchColumn();
        $debug['question_count'] = $count;
        
        // Get sample
        $sample = $pdo->query("SELECT id, module, teil, content FROM question_pools LIMIT 1")->fetch();
        $debug['sample'] = $sample;
    }
    
    echo json_encode(['success' => true, 'debug' => $debug]);
    
} catch (Exception $e) {
    $debug['error'] = $e->getMessage();
    echo json_encode(['success' => false, 'debug' => $debug]);
}
