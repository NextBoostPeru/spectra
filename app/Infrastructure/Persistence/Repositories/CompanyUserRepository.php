<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\CompanyUser;
use App\Domain\Exceptions\DomainException;
use App\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class CompanyUserRepository extends PdoRepository implements CompanyUserRepositoryInterface
{
    public function findActiveForUser(string $userId): ?CompanyUser
    {
        return $this->guard(function () use ($userId) {
            $baseQuery = 'SELECT id, company_id, user_id, status, active_company, deleted_at FROM company_users WHERE user_id = :user_id AND status = :status';
            $query = $this->withSoftDeleteScope($baseQuery);
            $query .= ' ORDER BY active_company DESC, created_at ASC LIMIT 1';

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':user_id', $userId);
            $statement->bindValue(':status', 'active');
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function assertMembership(string $userId, string $companyId): CompanyUser
    {
        return $this->guard(function () use ($userId, $companyId) {
            $baseQuery = 'SELECT id, company_id, user_id, status, active_company, deleted_at FROM company_users WHERE user_id = :user_id AND company_id = :company_id AND status = :status';
            $query = $this->withSoftDeleteScope($baseQuery);

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':user_id', $userId);
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':status', 'active');
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                throw new DomainException('El usuario no pertenece a la empresa solicitada.');
            }

            $membership = $this->hydrate($row);
            $membership->assertUsable();

            return $membership;
        });
    }

    public function setActiveCompany(string $userId, string $companyId): CompanyUser
    {
        return $this->guard(function () use ($userId, $companyId) {
            $membership = $this->assertMembership($userId, $companyId);

            $reset = $this->connection->prepare('UPDATE company_users SET active_company = 0 WHERE user_id = :user_id');
            $reset->bindValue(':user_id', $userId);
            $reset->execute();

            $activate = $this->connection->prepare('UPDATE company_users SET active_company = 1 WHERE user_id = :user_id AND company_id = :company_id');
            $activate->bindValue(':user_id', $userId);
            $activate->bindValue(':company_id', $companyId);
            $activate->execute();

            return new CompanyUser(
                id: $membership->id(),
                companyId: $membership->companyId(),
                userId: $membership->userId(),
                status: 'active',
                isActive: true,
            );
        });
    }

    public function createMembership(string $companyId, string $userId, string $status = 'active', bool $isActive = false): CompanyUser
    {
        return $this->guard(function () use ($companyId, $userId, $status, $isActive) {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO company_users (id, company_id, user_id, status, active_company)
VALUES (:id, :company_id, :user_id, :status, :active_company)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':user_id', $userId);
            $statement->bindValue(':status', $status);
            $statement->bindValue(':active_company', $isActive ? 1 : 0);
            $statement->execute();

            if ($isActive) {
                $this->setActiveCompany($userId, $companyId);
            }

            return $this->assertMembership($userId, $companyId);
        });
    }

    public function listForCompany(string $companyId): array
    {
        return $this->guard(function () use ($companyId) {
            $query = $this->withSoftDeleteScope('SELECT id, company_id, user_id, status, active_company, deleted_at FROM company_users WHERE company_id = :company_id');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            return array_map(fn (array $row): CompanyUser => $this->hydrate($row), $statement->fetchAll());
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): CompanyUser
    {
        $deletedAt = null;

        if (! empty($row['deleted_at'])) {
            $deletedAt = new DateTimeImmutable((string) $row['deleted_at']);
        }

        return new CompanyUser(
            id: (string) $row['id'],
            companyId: (string) $row['company_id'],
            userId: (string) $row['user_id'],
            status: (string) $row['status'],
            isActive: (bool) $row['active_company'],
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
