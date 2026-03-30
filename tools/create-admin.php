<?php
/**
 * Admin User Creator
 * Usage: php tools/create-admin.php
 */

require_once __DIR__ . '/../src/Database/Database.php';

use DTZ\Database\Database;

echo "🔧 DTZ Admin Creator\n";
echo "====================\n\n";

// Admin bilgileri
$adminEmail = 'admin@dtz-lernen.de';
$adminPassword = 'Admin123!';
$adminName = 'System Administrator';

try {
    $db = Database::getInstance();
    
    // Önce mevcut admin var mı kontrol et
    $existing = $db->selectOne("SELECT id FROM users WHERE email = ?", [$adminEmail]);
    
    if ($existing) {
        // Mevcut admini güncelle
        $db->update('users', [
            'password_hash' => password_hash($adminPassword, PASSWORD_ARGON2ID),
            'role' => 'admin',
            'is_active' => 1,
            'subscription_status' => 'premium'
        ], 'id = ?', [$existing['id']]);
        
        echo "✅ Mevcut admin güncellendi!\n";
    } else {
        // Yeni admin oluştur
        $userId = $db->insert('users', [
            'email' => $adminEmail,
            'password_hash' => password_hash($adminPassword, PASSWORD_ARGON2ID),
            'display_name' => $adminName,
            'role' => 'admin',
            'level' => 'B1',
            'subscription_status' => 'premium',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo "✅ Yeni admin oluşturuldu!\n";
    }
    
    echo "\n📧 Email: {$adminEmail}\n";
    echo "🔑 Password: {$adminPassword}\n";
    echo "👤 Name: {$adminName}\n";
    echo "\n🌐 Admin Panel: http://localhost:8080/frontend/admin/index.html\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
