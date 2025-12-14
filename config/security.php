<?php

declare(strict_types=1);

return [
    'force_https' => filter_var(env('FORCE_HTTPS', env('APP_ENV', 'local') === 'production'), FILTER_VALIDATE_BOOL),
    'csp' => env('SECURITY_CSP', "default-src 'self'"),
    'referrer_policy' => env('REFERRER_POLICY', 'no-referrer'),
    'frame_options' => env('FRAME_OPTIONS', 'DENY'),
    'cors' => [
        'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))),
        'allowed_methods' => env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
        'allowed_headers' => env('CORS_ALLOWED_HEADERS', 'Authorization,Content-Type,Accept'),
        'max_age' => (int) env('CORS_MAX_AGE', 600),
    ],
    'rate_limit' => [
        'max_attempts' => (int) env('RATE_LIMIT_MAX_ATTEMPTS', 60),
        'window_seconds' => (int) env('RATE_LIMIT_WINDOW_SECONDS', 60),
    ],
];
