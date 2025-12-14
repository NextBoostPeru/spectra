<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class UserRepository extends PdoRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        return $this->guard(function () use ($id) {
            $query = $this->withSoftDeleteScope('SELECT id, email, password_hash, status, platform_role, deleted_at FROM users WHERE id = :id');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $id);
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function findByEmail(string $email): ?User
    {
        return $this->guard(function () use ($email) {
            $query = $this->withSoftDeleteScope('SELECT id, email, password_hash, status, platform_role, deleted_at FROM users WHERE email = :email');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':email', $email);
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function recordLogin(string $userId, ?string $ip, ?string $userAgent): void
    {
        $this->guard(function () use ($userId) {
            $statement = $this->connection->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
            $statement->bindValue(':id', $userId);
            $statement->execute();
        });
    }

    public function create(string $email, string $passwordHash, string $platformRole = 'user', string $status = 'active'): User
    {
        return $this->guard(function () use ($email, $passwordHash, $platformRole, $status) {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO users (id, email, password_hash, status, platform_role)
VALUES (:id, :email, :password_hash, :status, :platform_role)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':email', strtolower($email));
            $statement->bindValue(':password_hash', $passwordHash);
            $statement->bindValue(':status', $status);
            $statement->bindValue(':platform_role', $platformRole);
            $statement->execute();

            return $this->findById($id);
        });
    }

    public function updateStatus(string $userId, string $status): void
    {
        $this->guard(function () use ($userId, $status) {
            $statement = $this->connection->prepare('UPDATE users SET status = :status WHERE id = :id');
            $statement->bindValue(':status', $status);
            $statement->bindValue(':id', $userId);
            $statement->execute();
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): User
    {
        $deletedAt = null;

        if (! empty($row['deleted_at'])) {
            $deletedAt = new DateTimeImmutable((string) $row['deleted_at']);
        }

        return new User(
            id: (string) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            status: (string) $row['status'],
            platformRole: (string) $row['platform_role'],
            deletedAt: $deletedAt,
        );
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }
}
