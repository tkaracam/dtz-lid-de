<?php
/**
 * Complete System Test
 * Tests all major components
 */

require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/JWT.php';
require_once __DIR__ . '/../src/Auth/AuthController.php';

use DTZ\Database\Database;
use DTZ\Auth\JWT;
use DTZ\Auth\AuthController;

echo "=== DTZ Learning Platform - Complete Test ===\n\n";

// Test 1: Database
echo "[1/5] Testing Database Connection... ";
try {
    $_ENV['DB_DRIVER'] = 'sqlite';
    $_ENV['DB_PATH'] = __DIR__ . '/../database/dtz_learning.db';
    
    $db = Database::getInstance();
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Tables
echo "[2/5] Checking Database Tables... ";
$requiredTables = ['users', 'question_pools', 'modelltest_attempts', 'user_progress', 'writing_submissions'];
$existingTables = $db->select("SELECT name FROM sqlite_master WHERE type='table'");
$existingNames = array_column($existingTables, 'name');

$missing = array_diff($requiredTables, $existingNames);
if (empty($missing)) {
    echo "✓ OK (" . count($requiredTables) . " tables)\n";
} else {
    echo "✗ MISSING: " . implode(', ', $missing) . "\n";
}

// Test 3: Questions
echo "[3/5] Checking Sample Questions... ";
$counts = $db->select("SELECT module, COUNT(*) as cnt FROM question_pools GROUP BY module");
$total = array_sum(array_column($counts, 'cnt'));
if ($total > 0) {
    echo "✓ OK ($total questions)\n";
    foreach ($counts as $c) {
        echo "      - {$c['module']}: {$c['cnt']}\n";
    }
} else {
    echo "✗ NO QUESTIONS\n";
}

// Test 4: JWT
echo "[4/5] Testing JWT... ";
try {
    $jwt = new JWT('test-secret');
    $token = $jwt->generate(['sub' => 1, 'email' => 'test@test.com']);
    $payload = $jwt->verify($token);
    
    if ($payload && $payload['sub'] === 1) {
        echo "✓ OK\n";
    } else {
        echo "✗ VERIFY FAILED\n";
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

// Test 5: AuthController
echo "[5/5] Testing AuthController... ";
try {
    $auth = new AuthController();
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Completed ===\n";
echo "\nNext steps:\n";
echo "1. Start server: php -S localhost:8080\n";
echo "2. Open: http://localhost:8080/frontend/\n";
echo "3. Login with: test@example.com / test123\n";
