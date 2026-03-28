#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Database Migration Tool
 * 
 * Usage:
 *   php tools/migrate.php           # Run migrations
 *   php tools/migrate.php --seed    # Run migrations + seed data
 *   php tools/migrate.php --fresh   # Drop all tables and recreate
 */

require_once __DIR__ . '/../src/Database/Database.php';

use DTZ\Database\Database;

$options = getopt('', ['seed', 'fresh', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
DTZ-LID Database Migration Tool

Usage:
  php tools/migrate.php           Run pending migrations
  php tools/migrate.php --seed    Run migrations and seed data
  php tools/migrate.php --fresh   Drop all tables and recreate (DANGER!)
  php tools/migrate.php --help    Show this help

Environment Variables:
  DB_TYPE=sqlite|pgsql|mysql      Database type (default: sqlite)
  DB_PATH=/path/to/db.sqlite      SQLite database path
  DB_HOST=localhost               Database host
  DB_PORT=5432                    Database port
  DB_NAME=dtz_lid                 Database name
  DB_USER=dtz_user                Database user
  DB_PASS=secret                  Database password

HELP;
    exit(0);
}

try {
    $db = Database::getInstance();
    $config = require __DIR__ . '/../config/database.php';
    
    echo "=== DTZ-LID Database Migration ===\n\n";
    
    $dbPath = $config['connections']['sqlite']['path'] ?? __DIR__ . '/../database/dtz_lid.db';
    
    // Fresh start (drop all)
    if (isset($options['fresh'])) {
        echo "⚠️  FRESH START: Dropping all tables...\n";
        
        $tables = [
            'migrations', 'audit_logs', 'user_sessions', 'user_question_history',
            'user_progress_cache', 'daily_stats', 'user_answers', 'payments',
            'subscriptions', 'question_pools', 'users'
        ];
        
        foreach ($tables as $table) {
            try {
                $db->execute("DROP TABLE IF EXISTS {$table}");
                echo "  ✓ Dropped table: {$table}\n";
            } catch (Exception $e) {
                echo "  - Table not found: {$table}\n";
            }
        }
        echo "\n";
    }
    
    // Run migrations using sqlite3 CLI for complex SQL
    echo "🔄 Running migrations...\n";
    
    $migrationFile = __DIR__ . '/../database/migrations/001_initial_schema.sql';
    
    if ($config['default'] === 'sqlite') {
        // Use sqlite3 command for complex migrations with triggers
        $command = "sqlite3 \"{$dbPath}\" < \"{$migrationFile}\" 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Migration failed: " . implode("\n", $output));
        }
        
        // Record migration
        $db->execute("CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY, migration VARCHAR(255), executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $db->execute("INSERT OR IGNORE INTO migrations (migration) VALUES (?)", ['001_initial_schema.sql']);
        
    } else {
        // For PostgreSQL/MySQL, use PDO
        $db->migrate();
    }
    
    echo "✅ Migrations completed\n\n";
    
    // Seed data
    if (isset($options['seed']) || isset($options['fresh'])) {
        echo "🌱 Seeding database...\n";
        
        $seedFile = __DIR__ . '/../database/seeds/001_sample_questions.sql';
        
        if ($config['default'] === 'sqlite') {
            $command = "sqlite3 \"{$dbPath}\" < \"{$seedFile}\" 2>&1";
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Seeding failed: " . implode("\n", $output));
            }
        } else {
            $db->seed();
        }
        
        echo "✅ Seed completed\n\n";
    }
    
    // Show statistics
    echo "📊 Database Statistics:\n";
    
    try {
        $userCount = $db->selectOne("SELECT COUNT(*) as count FROM users");
        echo "  - Users: " . ($userCount['count'] ?? 0) . "\n";
    } catch (Exception $e) {
        echo "  - Users: 0\n";
    }
    
    try {
        $questionCount = $db->selectOne("SELECT COUNT(*) as count FROM question_pools");
        echo "  - Questions: " . ($questionCount['count'] ?? 0) . "\n";
    } catch (Exception $e) {
        echo "  - Questions: 0\n";
    }
    
    try {
        $subscriptionCount = $db->selectOne("SELECT COUNT(*) as count FROM subscriptions");
        echo "  - Subscriptions: " . ($subscriptionCount['count'] ?? 0) . "\n";
    } catch (Exception $e) {
        echo "  - Subscriptions: 0\n";
    }
    
    echo "\n✨ Done!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (isset($options['fresh'])) {
        echo $e->getTraceAsString() . "\n";
    }
    exit(1);
}
