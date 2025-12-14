<?php

declare(strict_types=1);

namespace App\Application\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Application\Exceptions\ApplicationException;

class JwtTokenManager
{
    public function __construct(private readonly array $config)
    {
        if (($this->config['secret'] ?? '') === '') {
            throw new ApplicationException('Configura JWT_SECRET para emitir tokens.');
        }
    }

    public function createAccessToken(string $userId, string $sessionId, array $extraClaims = []): string
    {
        $now = time();
        $payload = array_merge($extraClaims, [
            'iss' => $this->config['issuer'],
            'aud' => $this->config['audience'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (int) $this->config['access_ttl_seconds'],
            'sub' => $userId,
            'sid' => $sessionId,
        ]);

        return JWT::encode($payload, $this->config['secret'], $this->config['algo']);
    }

    public function accessTtlSeconds(): int
    {
        return (int) $this->config['access_ttl_seconds'];
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->config['secret'], $this->config['algo']));

        return (array) $decoded;
    }
}
