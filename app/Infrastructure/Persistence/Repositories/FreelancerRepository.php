<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\Freelancer;
use App\Domain\Repositories\FreelancerRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class FreelancerRepository extends PdoRepository implements FreelancerRepositoryInterface
{
    public function paginate(int $page, int $pageSize): array
    {
        return $this->guard(function () use ($page, $pageSize): array {
            $offset = max(0, ($page - 1) * $pageSize);
            $query = $this->withSoftDeleteScope('SELECT id, full_name, email, status, deleted_at FROM freelancers ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll();
        });
    }

    public function count(): int
    {
        return $this->guard(function (): int {
            $statement = $this->connection->query('SELECT COUNT(*) AS total FROM freelancers WHERE deleted_at IS NULL');
            $row = $statement?->fetch();

            return (int) ($row['total'] ?? 0);
        });
    }

    public function create(array $data): Freelancer
    {
        return $this->guard(function () use ($data): Freelancer {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO freelancers (id, full_name, email, status)
VALUES (:id, :full_name, :email, :status)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':full_name', $data['full_name']);
            $statement->bindValue(':email', $data['email']);
            $statement->bindValue(':status', $data['status'] ?? 'pending');
            $statement->execute();

            return $this->findById($id);
        });
    }

    public function findById(string $id): ?Freelancer
    {
        return $this->guard(function () use ($id): ?Freelancer {
            $query = $this->withSoftDeleteScope('SELECT id, full_name, email, status, deleted_at FROM freelancers WHERE id = :id');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $id);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            $deletedAt = null;
            if (! empty($row['deleted_at'])) {
                $deletedAt = new DateTimeImmutable((string) $row['deleted_at']);
            }

            return new Freelancer(
                id: (string) $row['id'],
                fullName: (string) $row['full_name'],
                email: (string) $row['email'],
                status: (string) $row['status'],
                deletedAt: $deletedAt,
            );
        });
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
