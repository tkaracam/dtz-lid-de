<?php
/**
 * First-time setup endpoint
 * Creates admin user if not exists
 * Call once after deployment: /api/admin/setup.php
 */

require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Database\Database;

header('Content-Type: application/json');

// Simple security - only allow first time or with secret
$secret = $_GET['secret'] ?? '';
$expectedSecret = $_ENV['SETUP_SECRET'] ?? 'setup123';

try {
    $db = Database::getInstance();
    
    // Check if any admin exists
    $adminExists = $db->selectOne(
        "SELECT id FROM users WHERE role = 'admin' LIMIT 1"
    );
    
    if ($adminExists && $secret !== $expectedSecret) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Setup already completed. Use secret to recreate.'
        ]);
        exit;
    }
    
    // Create or update admin
    $adminEmail = 'admin@dtz-lid.de';
    $adminPassword = 'Admin123!';
    $passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID);
    
    // Check if admin email exists
    $existing = $db->selectOne(
        "SELECT id FROM users WHERE email = ?",
        [$adminEmail]
    );
    
    if ($existing) {
        // Update to admin
        $db->update('users', [
            'role' => 'admin',
            'password_hash' => $passwordHash,
            'subscription_status' => 'premium'
        ], 'id = ?', [$existing['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Admin user updated',
            'email' => $adminEmail,
            'password' => $adminPassword
        ]);
    } else {
        // Create new admin
        $userId = $db->insert('users', [
            'email' => $adminEmail,
            'display_name' => 'Administrator',
            'password_hash' => $passwordHash,
            'role' => 'admin',
            'subscription_status' => 'premium',
            'level' => 'B1'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Admin user created',
            'email' => $adminEmail,
            'password' => $adminPassword
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
