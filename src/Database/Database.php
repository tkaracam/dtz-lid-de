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
    private array $queryLog = [];
    
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
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    // Enable foreign keys for SQLite
                    $pdo->exec('PRAGMA foreign_keys = ON');
                    break;
                    
                case 'pgsql':
                    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
                    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                    break;
                    
                case 'mysql':
                    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                    break;
                    
                default:
                    throw new RuntimeException("Unsupported database driver: {$driver}");
            }
            
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new RuntimeException("Database connection failed");
        }
    }
    
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    /**
     * Execute a SELECT query
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a SELECT query and return single row
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Execute INSERT query
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Execute UPDATE query
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $stmt = $this->execute($sql, array_merge(array_values($data), $whereParams));
        
        return $stmt->rowCount();
    }
    
    /**
     * Execute DELETE query
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Execute raw SQL
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $this->logQuery($sql, $params);
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
    
    /**
     * Run migrations
     */
    public function migrate(): void
    {
        $this->createMigrationsTable();
        
        $migrations = $this->getPendingMigrations();
        
        foreach ($migrations as $migration) {
            $this->runMigration($migration);
        }
    }
    
    private function createMigrationsTable(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    private function getPendingMigrations(): array
    {
        $executed = $this->select("SELECT migration FROM migrations");
        $executedNames = array_column($executed, 'migration');
        
        $files = glob($this->config['migrations']['directory'] . '/*.sql');
        sort($files);
        
        return array_filter($files, function ($file) use ($executedNames) {
            $name = basename($file);
            return !in_array($name, $executedNames);
        });
    }
    
    private function runMigration(string $file): void
    {
        $sql = file_get_contents($file);
        
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split by semicolon for multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $this->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                // Skip empty statements and standalone comments
                if (!empty($statement) && strlen(trim($statement)) > 5) {
                    $this->execute($statement);
                }
            }
            
            $this->insert('migrations', ['migration' => basename($file)]);
            $this->commit();
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Run seed files
     */
    public function seed(): void
    {
        $seedDir = __DIR__ . '/../../database/seeds';
        $files = glob($seedDir . '/*.sql');
        sort($files);
        
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            
            // Remove comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && strlen(trim($statement)) > 5) {
                    $this->execute($statement);
                }
            }
        }
    }
    
    /**
     * JSON helper for SQLite/PostgreSQL compatibility
     */
    public function jsonField(string $column, string $path): string
    {
        $driver = $this->config['default'];
        
        switch ($driver) {
            case 'sqlite':
                return "json_extract({$column}, '{$path}')";
            case 'pgsql':
                return "{$column}->>'{$path}'";
            case 'mysql':
                return "JSON_UNQUOTE(JSON_EXTRACT({$column}, '{$path}'))";
            default:
                return $column;
        }
    }
    
    /**
     * Check if JSON field contains value
     */
    public function jsonContains(string $column, string $value): string
    {
        $driver = $this->config['default'];
        
        switch ($driver) {
            case 'sqlite':
                return "{$column} LIKE '%{$value}%'";
            case 'pgsql':
                return "{$column}::text LIKE '%{$value}%'";
            case 'mysql':
                return "JSON_CONTAINS({$column}, '\"{$value}\"')";
            default:
                return "{$column} LIKE '%{$value}%'";
        }
    }
    
    private function logQuery(string $sql, array $params): void
    {
        if ($this->config['logging']) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'time' => microtime(true),
            ];
        }
    }
    
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
}
