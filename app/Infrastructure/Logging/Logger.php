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
        $this->log('INFO', $message, $this->sanitizeContext($context));
    }

    /**
     * @param array<string, scalar|array|null> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $this->sanitizeContext($context));
    }

    /**
     * @param array<string, scalar|array|null> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $this->sanitizeContext($context));
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

    /**
     * @param array<string, scalar|array|null> $context
     * @return array<string, scalar|array|null>
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'pass',
            'pwd',
            'token',
            'access_token',
            'refresh_token',
            'authorization',
            'document',
            'document_number',
            'secret',
        ];

        $sanitized = [];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
                continue;
            }

            if (is_string($value) && strlen($value) > 256) {
                $sanitized[$key] = substr($value, 0, 256) . '...';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
