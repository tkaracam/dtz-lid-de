<?php
/**
 * Simple admin login test
 * Run: php admin-login-test.php
 */

require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/JWT.php';

use DTZ\Database\Database;
use DTZ\Auth\JWT;

$email = 'admin@dtz-lid.de';
$password = 'Admin123!';

try {
    $db = Database::getInstance();
    
    // Check admin exists
    $user = $db->selectOne(
        "SELECT id, email, display_name, password_hash, role FROM users WHERE email = ?",
        [$email]
    );
    
    if (!$user) {
        echo "❌ Admin kullanıcısı bulunamadı!\n";
        echo "Setup sayfasını çalıştır: /api/admin/setup.php\n";
        exit(1);
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        echo "❌ Şifre yanlış!\n";
        exit(1);
    }
    
    // Create JWT
    $jwt = new JWT($_ENV['JWT_SECRET'] ?? 'dtz-secret-key');
    $token = $jwt->generate([
        'sub' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);
    
    echo "✅ Admin girişi BAŞARILI!\n\n";
    echo "Token: $token\n\n";
    echo "Kullanıcı:\n";
    print_r($user);
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
