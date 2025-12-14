<?php

require_once __DIR__ . '/../src/Support/Response.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Controllers/GenericController.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

use App\Controllers\GenericController;
use App\Controllers\AuthController;
use App\Database;
use App\Support\Response;

$config = require __DIR__ . '/../config/config.php';

$database = new Database($config['db']);
$controller = new GenericController($database, $config['pagination']);
$authController = new AuthController($database);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $path);

if ($segments[0] !== 'api') {
    Response::json(['message' => 'API básica en funcionamiento'], 200);
    return;
}

$table = $segments[1] ?? null;
$id = $segments[2] ?? null;

if (!$table) {
    Response::error('Debe indicar la tabla a consultar', 400);
    return;
}

if ($table === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Solo se permite el método POST para login', 405);
        return;
    }

    $authController->login();
    return;
}

$controller->handle($table, $id, $_SERVER['REQUEST_METHOD']);
