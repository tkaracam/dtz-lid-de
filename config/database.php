<?php
declare(strict_types=1);

/**
 * Database Configuration
 * 
 * Environment variables:
 * - DB_TYPE: sqlite | pgsql | mysql
 * - DB_HOST: Database host (for pgsql/mysql)
 * - DB_PORT: Database port
 * - DB_NAME: Database name/path
 * - DB_USER: Username
 * - DB_PASS: Password
 * - DB_CHARSET: Character set (default: utf8mb4)
 */

return [
    'default' => $_ENV['DB_TYPE'] ?? 'sqlite',
    
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'path' => $_ENV['DB_PATH'] ?? __DIR__ . '/../database/dtz_lid.db',
            'charset' => 'utf8',
        ],
        
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 5432,
            'database' => $_ENV['DB_NAME'] ?? 'dtz_lid',
            'username' => $_ENV['DB_USER'] ?? 'dtz_user',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_NAME'] ?? 'dtz_lid',
            'username' => $_ENV['DB_USER'] ?? 'dtz_user',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
    
    // Migration settings
    'migrations' => [
        'table' => 'migrations',
        'directory' => __DIR__ . '/../database/migrations',
    ],
    
    // Query logging (development only)
    'logging' => ($_ENV['DB_LOGGING'] ?? 'false') === 'true',
];
