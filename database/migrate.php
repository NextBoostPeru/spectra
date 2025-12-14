<?php

declare(strict_types=1);

use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../config/bootstrap.php';

$command = $_SERVER['argv'][1] ?? 'migrate';

if (! in_array($command, ['migrate', 'seed'], true)) {
    echo "Comando no soportado. Usa 'migrate' o 'seed'." . PHP_EOL;
    exit(1);
}

$application = new PhinxApplication();
$application->setAutoExit(false);

$input = $command === 'seed'
    ? new StringInput('seed:run -c config/phinx.php')
    : new StringInput('migrate -c config/phinx.php');

$exitCode = $application->run($input, new ConsoleOutput());

if ($exitCode !== 0) {
    echo "El comando de migración falló con código {$exitCode}." . PHP_EOL;
}

exit($exitCode);
