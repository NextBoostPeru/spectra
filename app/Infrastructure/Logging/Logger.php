<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use DateTimeImmutable;

/**
 * Logger mínimo basado en error_log para ambientes sin dependencia externa.
 */
class Logger
{
    public function __construct(private readonly string $channel = 'app')
    {
    }

    /**
     * @param array<string, scalar|array|null> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * @param array<string, scalar|array|null> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * @param array<string, scalar|array|null> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * @param array<string, scalar|array|null> $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $timestamp = (new DateTimeImmutable())->format(DATE_ATOM);
        $payload = json_encode($context, JSON_THROW_ON_ERROR);

        error_log("[{$timestamp}] {$this->channel}.{$level}: {$message} {$payload}");
    }
}
