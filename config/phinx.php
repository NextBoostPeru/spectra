<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

return [
    'paths' => [
        'migrations' => __DIR__ . '/../database/migrations',
        'seeds' => __DIR__ . '/../database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'name' => env('DB_DATABASE', 'spectra'),
            'user' => env('DB_USERNAME', 'root'),
            'pass' => env('DB_PASSWORD', ''),
            'port' => (int) env('DB_PORT', 3306),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
    'version_order' => 'execution',
];
