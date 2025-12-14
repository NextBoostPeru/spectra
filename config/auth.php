<?php

declare(strict_types=1);

return [
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'issuer' => env('APP_URL', 'http://localhost'),
        'audience' => env('JWT_AUDIENCE', 'spectra-clients'),
        'access_ttl_seconds' => (int) env('JWT_ACCESS_TTL', 900), // 15 minutos por defecto
        'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', 15),
        'algo' => 'HS256',
    ],
    'lockout' => [
        'max_attempts' => (int) env('AUTH_MAX_ATTEMPTS', 5),
        'window_seconds' => (int) env('AUTH_ATTEMPT_WINDOW', 900),
        'lock_seconds' => (int) env('AUTH_LOCK_SECONDS', 900),
    ],
    'sso' => [
        'providers' => [
            'google' => [
                'issuer' => 'https://accounts.google.com',
                'audience' => env('GOOGLE_CLIENT_ID', ''),
            ],
            'microsoft' => [
                'issuer' => 'https://login.microsoftonline.com/common/v2.0',
                'audience' => env('MICROSOFT_CLIENT_ID', ''),
            ],
        ],
    ],
    'tenant' => [
        'company_header' => env('TENANT_HEADER', 'X-Company-ID'),
        'company_claim' => 'company_id',
    ],
];
