<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Exceptions\ApplicationException;

class LoginAttemptLimiter
{
    public function __construct(
        private readonly string $storagePath,
        private readonly int $maxAttempts,
        private readonly int $windowSeconds,
        private readonly int $lockSeconds,
    ) {
        if (! is_dir($this->storagePath) && ! mkdir($this->storagePath, 0o775, true) && ! is_dir($this->storagePath)) {
            throw new ApplicationException('No se pudo preparar el almacenamiento para intentos de acceso.');
        }
    }

    public function ensureNotLocked(string $key): void
    {
        $payload = $this->readPayload($key);
        $now = time();

        if ($payload['locked_until'] > $now) {
            throw new ApplicationException('Demasiados intentos fallidos, vuelve a intentarlo en unos minutos.');
        }

        if (($now - $payload['window_started_at']) > $this->windowSeconds) {
            $this->writePayload($key, $this->freshPayload($now));
        }
    }

    public function registerFailure(string $key): void
    {
        $payload = $this->readPayload($key);
        $now = time();

        if (($now - $payload['window_started_at']) > $this->windowSeconds) {
            $payload = $this->freshPayload($now);
        }

        $payload['attempts']++;

        if ($payload['attempts'] >= $this->maxAttempts) {
            $payload['locked_until'] = $now + $this->lockSeconds;
        }

        $this->writePayload($key, $payload);
    }

    public function registerSuccess(string $key): void
    {
        $this->writePayload($key, $this->freshPayload(time()));
    }

    /**
     * @return array{attempts:int,locked_until:int,window_started_at:int}
     */
    private function readPayload(string $key): array
    {
        $file = $this->path($key);

        if (! is_file($file)) {
            return $this->freshPayload(time());
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            return $this->freshPayload(time());
        }

        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return [
            'attempts' => (int) ($decoded['attempts'] ?? 0),
            'locked_until' => (int) ($decoded['locked_until'] ?? 0),
            'window_started_at' => (int) ($decoded['window_started_at'] ?? time()),
        ];
    }

    private function writePayload(string $key, array $payload): void
    {
        file_put_contents($this->path($key), json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function freshPayload(int $now): array
    {
        return [
            'attempts' => 0,
            'locked_until' => 0,
            'window_started_at' => $now,
        ];
    }

    private function path(string $key): string
    {
        return rtrim($this->storagePath, '/').'/'.sha1($key).'.json';
    }
}
