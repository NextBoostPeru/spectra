<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use RuntimeException;

class RateLimiter
{
    public function __construct(private readonly string $storagePath)
    {
        if (! is_dir($this->storagePath) && ! mkdir($this->storagePath, 0o775, true) && ! is_dir($this->storagePath)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento para rate limiting.');
        }
    }

    public function allow(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $now = time();
        $payload = $this->readPayload($key);

        if ($payload['expires_at'] <= $now) {
            $payload = [
                'attempts' => 0,
                'expires_at' => $now + $decaySeconds,
            ];
        }

        $payload['attempts']++;
        $this->writePayload($key, $payload);

        return $payload['attempts'] <= $maxAttempts;
    }

    /**
     * @return array{attempts:int,expires_at:int}
     */
    private function readPayload(string $key): array
    {
        $file = $this->filePath($key);

        if (! is_file($file)) {
            return [
                'attempts' => 0,
                'expires_at' => time(),
            ];
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            return [
                'attempts' => 0,
                'expires_at' => time(),
            ];
        }

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return [
            'attempts' => (int) ($data['attempts'] ?? 0),
            'expires_at' => (int) ($data['expires_at'] ?? time()),
        ];
    }

    private function writePayload(string $key, array $payload): void
    {
        $file = $this->filePath($key);
        file_put_contents($file, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function filePath(string $key): string
    {
        return rtrim($this->storagePath, '/').'/'.sha1($key).'.json';
    }
}
