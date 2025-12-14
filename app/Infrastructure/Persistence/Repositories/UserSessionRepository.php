<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\UserSession;
use App\Domain\Repositories\UserSessionRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class UserSessionRepository extends PdoRepository implements UserSessionRepositoryInterface
{
    public function create(string $userId, string $refreshTokenHash, ?string $ip, ?string $userAgent): UserSession
    {
        return $this->guard(function () use ($userId, $refreshTokenHash, $ip, $userAgent) {
            $id = $this->uuid();
            $statement = $this->connection->prepare('INSERT INTO user_sessions (id, user_id, refresh_token_hash, status, last_ip, last_user_agent, created_at) VALUES (:id, :user_id, :refresh_hash, :status, :ip, :user_agent, NOW())');
            $statement->bindValue(':id', $id);
            $statement->bindValue(':user_id', $userId);
            $statement->bindValue(':refresh_hash', $refreshTokenHash);
            $statement->bindValue(':status', 'active');
            $statement->bindValue(':ip', $ip);
            $statement->bindValue(':user_agent', $userAgent);
            $statement->execute();

            return new UserSession(
                id: $id,
                userId: $userId,
                refreshTokenHash: $refreshTokenHash,
                status: 'active',
                createdAt: new DateTimeImmutable(),
                lastIp: $ip,
                lastUserAgent: $userAgent,
            );
        });
    }

    public function findActiveByRefreshHash(string $hash): ?UserSession
    {
        return $this->guard(function () use ($hash) {
            $statement = $this->connection->prepare('SELECT id, user_id, refresh_token_hash, status, created_at, revoked_at, last_ip, last_user_agent FROM user_sessions WHERE refresh_token_hash = :hash AND status = :status LIMIT 1');
            $statement->bindValue(':hash', $hash);
            $statement->bindValue(':status', 'active');
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function rotateToken(string $sessionId, string $newRefreshTokenHash, ?string $ip, ?string $userAgent): UserSession
    {
        return $this->guard(function () use ($sessionId, $newRefreshTokenHash, $ip, $userAgent) {
            $statement = $this->connection->prepare('UPDATE user_sessions SET refresh_token_hash = :hash, last_ip = :ip, last_user_agent = :user_agent WHERE id = :id');
            $statement->bindValue(':hash', $newRefreshTokenHash);
            $statement->bindValue(':ip', $ip);
            $statement->bindValue(':user_agent', $userAgent);
            $statement->bindValue(':id', $sessionId);
            $statement->execute();

            return $this->findById($sessionId);
        });
    }

    public function revokeByRefreshHash(string $hash): void
    {
        $this->guard(function () use ($hash) {
            $statement = $this->connection->prepare('UPDATE user_sessions SET status = :status, revoked_at = NOW() WHERE refresh_token_hash = :hash');
            $statement->bindValue(':status', 'revoked');
            $statement->bindValue(':hash', $hash);
            $statement->execute();
        });
    }

    private function findById(string $sessionId): UserSession
    {
        $statement = $this->connection->prepare('SELECT id, user_id, refresh_token_hash, status, created_at, revoked_at, last_ip, last_user_agent FROM user_sessions WHERE id = :id');
        $statement->bindValue(':id', $sessionId);
        $statement->execute();

        $row = $statement->fetch();

        if ($row === false) {
            throw new \RuntimeException('Sesión no encontrada tras rotar token.');
        }

        return $this->hydrate($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): UserSession
    {
        $createdAt = new DateTimeImmutable((string) $row['created_at']);
        $revokedAt = null;

        if (! empty($row['revoked_at'])) {
            $revokedAt = new DateTimeImmutable((string) $row['revoked_at']);
        }

        return new UserSession(
            id: (string) $row['id'],
            userId: (string) $row['user_id'],
            refreshTokenHash: (string) $row['refresh_token_hash'],
            status: (string) $row['status'],
            createdAt: $createdAt,
            revokedAt: $revokedAt,
            lastIp: $row['last_ip'] !== null ? (string) $row['last_ip'] : null,
            lastUserAgent: $row['last_user_agent'] !== null ? (string) $row['last_user_agent'] : null,
        );
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
