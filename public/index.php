<?php

require_once __DIR__ . '/../src/Support/Response.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Controllers/GenericController.php';

use App\Controllers\GenericController;
use App\Database;
use App\Support\Response;

$config = require __DIR__ . '/../config/config.php';

$database = new Database($config['db']);
$controller = new GenericController($database, $config['pagination']);

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

$controller->handle($table, $id, $_SERVER['REQUEST_METHOD']);
