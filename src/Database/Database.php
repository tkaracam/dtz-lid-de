<?php
declare(strict_types=1);

namespace DTZ\Database;

use PDO;
use PDOException;

class Database {
    private static ?self $instance = null;
    private PDO $pdo;
    
    private function __construct() {
        $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
        
        try {
            if ($driver === 'pgsql') {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? '5432';
                $name = $_ENV['DB_NAME'] ?? 'dtz_learning';
                $user = $_ENV['DB_USER'] ?? 'postgres';
                $pass = $_ENV['DB_PASS'] ?? '';
                $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                // SQLite - try multiple paths
                $dbPath = $_ENV['DB_PATH'] ?? '/var/www/html/database/dtz_production.db';
                
                // Ensure directory exists
                $dbDir = dirname($dbPath);
                if (!is_dir($dbDir)) {
                    @mkdir($dbDir, 0775, true);
                }
                
                $dsn = "sqlite:$dbPath";
                
                $this->pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // Set permissions
                if (file_exists($dbPath)) {
                    chmod($dbPath, 0664);
                }
                
                // Initialize tables if they don't exist
                $this->initializeTables();
            }
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function initializeTables(): void {
        try {
            // Check if users table exists
            $result = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            if ($result->fetch()) {
                return; // Tables already exist
            }
            
            // Create users table
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
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
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
            
        } catch (PDOException $e) {
            error_log('Table initialization error: ' . $e->getMessage());
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getPdo(): PDO {
        return $this->pdo;
    }
    
    public function select(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function selectOne(string $sql, array $params = []): ?array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function insert(string $table, array $data): string {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->pdo->lastInsertId();
    }
    
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "$column = ?";
        }
        $setStr = implode(', ', $set);
        
        $sql = "UPDATE $table SET $setStr WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));
        
        return $stmt->rowCount();
    }
    
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }
}
