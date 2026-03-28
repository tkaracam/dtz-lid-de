<?php
/**
 * Create admin user script
 * Run: php tools/create-admin.php
 */

require_once __DIR__ . '/../src/Database/Database.php';

use DTZ\Database\Database;

// Admin credentials
$adminEmail = 'admin@dtz-lid.de';
$adminPassword = 'Admin123!';
$adminName = 'Administrator';

try {
    $db = Database::getInstance();
    
    // Check if admin exists
    $existing = $db->selectOne(
        "SELECT id FROM users WHERE email = ?",
        [$adminEmail]
    );
    
    if ($existing) {
        // Update existing to admin
        $db->update('users', 
            ['role' => 'admin'],
            'id = ?',
            [$existing['id']]
        );
        echo "✅ Existing user updated to admin\n";
    } else {
        // Create new admin
        $passwordHash = password_hash($adminPassword, PASSWORD_ARGON2ID);
        
        $userId = $db->insert('users', [
            'email' => $adminEmail,
            'display_name' => $adminName,
            'password_hash' => $passwordHash,
            'role' => 'admin',
            'subscription_status' => 'premium',
            'level' => 'B1'
        ]);
        
        echo "✅ Admin user created successfully\n";
    }
    
    echo "\n📧 Email: $adminEmail\n";
    echo "🔑 Password: $adminPassword\n";
    echo "\n🔗 Admin Panel: https://www.dtz-lid.de/admin/login.html\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
