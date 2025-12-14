<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Interface\Http\Middleware\RateLimiter;
use App\Interface\Http\Middleware\SecurityHeaders;

$securityConfig = require __DIR__ . '/../config/security.php';
$security = new SecurityHeaders($securityConfig);
$security->enforceHttps();
$security->applySecurityHeaders();
$security->applyCors();

$rateLimiter = new RateLimiter(__DIR__ . '/../storage/cache/rate-limits');
$clientKey = ($_SERVER['REMOTE_ADDR'] ?? 'guest') . ':readiness';

if (! $rateLimiter->allow($clientKey, $securityConfig['rate_limit']['max_attempts'], $securityConfig['rate_limit']['window_seconds'])) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Too many requests',
    ]);
    exit;
}

header('Content-Type: application/json');

echo json_encode([
    'app' => env('APP_NAME', 'Spectra Backend'),
    'environment' => env('APP_ENV', 'local'),
    'status' => 'ready',
]);
