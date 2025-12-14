<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$rootPath = dirname(__DIR__);

$dotenv = Dotenv::createImmutable($rootPath, '.env', true);
$dotenv->safeLoad();

if (! isset($_ENV['APP_TIMEZONE'])) {
    date_default_timezone_set('UTC');
} else {
    date_default_timezone_set($_ENV['APP_TIMEZONE']);
}

if (! function_exists('env')) {
    /**
     * Retrieve an environment variable with an optional default fallback.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
