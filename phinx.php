<?php
/**
 * Phinx Database Migration Configuration
 * DTZ Learning Platform
 */

// Detect database type from environment or default to SQLite
$dbType = $_ENV['DB_TYPE'] ?? 'sqlite';

if ($dbType === 'sqlite') {
    $dbPath = __DIR__ . '/database/dtz.db';
    return [
        'paths' => [
            'migrations' => __DIR__ . '/db/migrations',
            'seeds' => __DIR__ . '/db/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'development',
            'production' => [
                'adapter' => 'sqlite',
                'name' => $dbPath,
                'charset' => 'utf8'
            ],
            'development' => [
                'adapter' => 'sqlite',
                'name' => $dbPath,
                'charset' => 'utf8'
            ],
            'testing' => [
                'adapter' => 'sqlite',
                'name' => __DIR__ . '/database/dtz_test.db',
                'charset' => 'utf8'
            ]
        ],
        'version_order' => 'creation'
    ];
} else {
    // PostgreSQL configuration
    return [
        'paths' => [
            'migrations' => __DIR__ . '/db/migrations',
            'seeds' => __DIR__ . '/db/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'development',
            'production' => [
                'adapter' => 'pgsql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'dtz_production',
                'user' => $_ENV['DB_USER'] ?? 'dtz_user',
                'pass' => $_ENV['DB_PASSWORD'] ?? '',
                'port' => $_ENV['DB_PORT'] ?? 5432,
                'charset' => 'utf8'
            ],
            'development' => [
                'adapter' => 'pgsql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'dtz_development',
                'user' => $_ENV['DB_USER'] ?? 'dtz_user',
                'pass' => $_ENV['DB_PASSWORD'] ?? '',
                'port' => $_ENV['DB_PORT'] ?? 5432,
                'charset' => 'utf8'
            ],
            'testing' => [
                'adapter' => 'pgsql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'dtz_test',
                'user' => $_ENV['DB_USER'] ?? 'dtz_user',
                'pass' => $_ENV['DB_PASSWORD'] ?? '',
                'port' => $_ENV['DB_PORT'] ?? 5432,
                'charset' => 'utf8'
            ]
        ],
        'version_order' => 'creation'
    ];
}
