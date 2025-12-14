<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

echo json_encode([
    'app' => env('APP_NAME', 'Spectra Backend'),
    'environment' => env('APP_ENV', 'local'),
    'status' => 'ready',
]);
