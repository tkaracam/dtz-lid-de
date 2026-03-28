<?php
declare(strict_types=1);

namespace DTZ\Database;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Database
{
    private static ?self $instance = null;
    private PDO $connection;
    private array $config;
    
    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/database.php';
        $this->connection = $this->createConnection();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function createConnection(): PDO
    {
        $driver = $this->config['default'];
        $config = $this->config['connections'][$driver];
        
        try {
            switch ($driver) {
                case 'sqlite':
                    $dsn = "sqlite:{$config['path']}";
                    $pdo = new PDO($dsn, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    $pdo->exec('PRAGMA foreign_keys = ON');
                    break;
                    
                case 'pgsql':
                    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
                    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                    break;
                    
                default:
                    throw new RuntimeException("Unsupported driver: {$driver}");
            }
            
            return $pdo;
        } catch (PDOException $e) {
            error_log("DB Connection failed: " . $e->getMessage());
            throw new RuntimeException("Database connection failed");
        }
    }
    
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        
        return $this->connection->lastInsertId();
    }
    
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $stmt = $this->execute($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }
    
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }
    
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
    
    /**
     * Run all pending migrations
     */
    public function migrate(): void
    {
        $driver = $this->config['default'];
        $migrationFile = $this->config['migrations']['directory'] . '/003_complete_platform.sql';
        
        if ($driver === 'sqlite') {
            // For SQLite, skip this complex migration (use PostgreSQL for full features)
            echo "⚠️  Full platform features require PostgreSQL. SQLite has limited support.\n";
            return;
        }
        
        // PostgreSQL - run full migration
        if (file_exists($migrationFile)) {
            $command = "PGPASSWORD='{$this->config['connections']['pgsql']['password']}' psql " .
                      "-h {$this->config['connections']['pgsql']['host']} " .
                      "-p {$this->config['connections']['pgsql']['port']} " .
                      "-U {$this->config['connections']['pgsql']['username']} " .
                      "-d {$this->config['connections']['pgsql']['database']} " .
                      "-f {$migrationFile} 2>&1";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new RuntimeException("Migration failed: " . implode("\n", $output));
            }
            
            echo "✅ PostgreSQL migration completed\n";
        }
    }
    
    private function __clone() {}
}
