<?php
declare(strict_types=1);

/**
 * Database Configuration
 * Supports SQLite (dev) and PostgreSQL (production)
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
            'charset' => 'utf8',
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
];
