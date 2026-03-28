<?php
/**
 * Database initialization script
 * Creates tables if they don't exist
 */

require_once __DIR__ . '/../src/Database/Database.php';

use DTZ\Database\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        display_name VARCHAR(100) NOT NULL,
        name VARCHAR(100),
        level VARCHAR(2) DEFAULT 'A2' CHECK (level IN ('A1', 'A2', 'B1', 'B2')),
        role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('user', 'admin')),
        subscription_status VARCHAR(20) DEFAULT 'free' 
            CHECK (subscription_status IN ('free', 'trialing', 'premium', 'expired', 'canceled')),
        trial_ends_at TIMESTAMP,
        is_active BOOLEAN DEFAULT 1,
        daily_goal INTEGER DEFAULT 10,
        streak_count INTEGER DEFAULT 0,
        last_activity_at TIMESTAMP,
        email_verified_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)");
    
    echo "✅ Database tables created successfully\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
