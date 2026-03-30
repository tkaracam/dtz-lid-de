<?php
declare(strict_types=1);

namespace DTZ\Database;

use PDO;
use PDOException;

class Database {
    private static ?self $instance = null;
    private PDO $pdo;
    
    private function __construct() {
        $driver = $this->env('DB_DRIVER', 'sqlite');
        
        try {
            if ($driver === 'pgsql') {
                $host = $this->env('DB_HOST', 'localhost');
                $port = $this->env('DB_PORT', '5432');
                $name = $this->env('DB_NAME', 'dtz_learning');
                $user = $this->env('DB_USER', 'postgres');
                $pass = $this->env('DB_PASS', '');
                $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                // SQLite - dynamic path resolution
                $possiblePaths = [
                    $this->env('DB_PATH'), // Environment variable first
                    __DIR__ . '/../../database/dtz.db', // Project root (local dev)
                    '/var/www/html/database/dtz_production.db', // Production
                    '/var/www/dtz-lid-de/database/dtz.db', // Alternative production
                    dirname(__DIR__, 2) . '/database/dtz.db', // Relative to src
                ];
                
                $dbPath = null;
                foreach ($possiblePaths as $path) {
                    if ($path && (file_exists($path) || is_writable(dirname($path)))) {
                        $dbPath = $path;
                        break;
                    }
                }
                
                if (!$dbPath) {
                    $dbPath = __DIR__ . '/../../database/dtz.db'; // Default fallback
                }
                
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

    private function env(string $key, $default = null)
    {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        return $val;
    }
    
    private function initializeTables(): void {
        try {
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

            // Core learning tables
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS question_pools (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                module VARCHAR(20) NOT NULL,
                teil INTEGER NOT NULL,
                level VARCHAR(2) NOT NULL,
                question_type VARCHAR(30) NOT NULL,
                content TEXT NOT NULL,
                media_urls TEXT,
                correct_answer TEXT NOT NULL,
                explanation TEXT,
                hints TEXT,
                difficulty INTEGER DEFAULT 5,
                points INTEGER DEFAULT 10,
                usage_count INTEGER DEFAULT 0,
                correct_rate DECIMAL(5,2),
                avg_time_seconds INTEGER,
                last_used_at TIMESTAMP,
                is_active BOOLEAN DEFAULT 1,
                is_premium_only BOOLEAN DEFAULT 0,
                created_by INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_questions_module_level_active ON question_pools(module, level, is_active)");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS user_answers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                question_id INTEGER NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                user_answer TEXT NOT NULL,
                is_correct BOOLEAN NOT NULL,
                points_earned INTEGER DEFAULT 0,
                time_spent_seconds INTEGER DEFAULT 0,
                attempts_count INTEGER DEFAULT 1,
                ai_feedback TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_answers_user_date ON user_answers(user_id, created_at)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_answers_question ON user_answers(question_id)");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS daily_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                date DATE NOT NULL,
                total_questions INTEGER DEFAULT 0,
                correct_count INTEGER DEFAULT 0,
                total_points INTEGER DEFAULT 0,
                total_time_minutes INTEGER DEFAULT 0,
                module_breakdown TEXT,
                goal_reached BOOLEAN DEFAULT 0,
                streak_maintained BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, date)
            )");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS user_question_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                question_id INTEGER NOT NULL,
                first_seen_at TIMESTAMP,
                last_seen_at TIMESTAMP,
                times_seen INTEGER DEFAULT 0,
                times_correct INTEGER DEFAULT 0,
                next_review_at TIMESTAMP,
                ease_factor DECIMAL(3,2) DEFAULT 2.50,
                interval_days INTEGER DEFAULT 0,
                UNIQUE(user_id, question_id)
            )");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_history_user_review ON user_question_history(user_id, next_review_at)");
            
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

    public function execute(string $sql, array $params = []): int {
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
