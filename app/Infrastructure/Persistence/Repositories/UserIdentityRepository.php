<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\UserIdentity;
use App\Domain\Repositories\UserIdentityRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class UserIdentityRepository extends PdoRepository implements UserIdentityRepositoryInterface
{
    public function findByProviderSubject(string $provider, string $subject): ?UserIdentity
    {
        return $this->guard(function () use ($provider, $subject) {
            $statement = $this->connection->prepare('SELECT id, user_id, provider, provider_subject, email_verified FROM user_identities WHERE provider = :provider AND provider_subject = :subject LIMIT 1');
            $statement->bindValue(':provider', $provider);
            $statement->bindValue(':subject', $subject);
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function linkIdentity(string $userId, string $provider, string $subject, bool $emailVerified): UserIdentity
    {
        return $this->guard(function () use ($userId, $provider, $subject, $emailVerified) {
            $id = $this->uuid();
            $statement = $this->connection->prepare('INSERT INTO user_identities (id, user_id, provider, provider_subject, email_verified, created_at) VALUES (:id, :user_id, :provider, :subject, :email_verified, NOW()) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), email_verified = VALUES(email_verified)');
            $statement->bindValue(':id', $id);
            $statement->bindValue(':user_id', $userId);
            $statement->bindValue(':provider', $provider);
            $statement->bindValue(':subject', $subject);
            $statement->bindValue(':email_verified', $emailVerified, \PDO::PARAM_BOOL);
            $statement->execute();

            return $this->findByProviderSubject($provider, $subject) ?? new UserIdentity($id, $userId, $provider, $subject, $emailVerified);
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): UserIdentity
    {
        return new UserIdentity(
            id: (string) $row['id'],
            userId: (string) $row['user_id'],
            provider: (string) $row['provider'],
            providerSubject: (string) $row['provider_subject'],
            emailVerified: (bool) $row['email_verified'],
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
