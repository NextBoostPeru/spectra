<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\ApprovalRequest;
use App\Domain\Entities\ApprovalStep;
use App\Domain\Repositories\ApprovalRequestRepositoryInterface;
use App\Domain\Repositories\ApprovalStepRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ApprovalRequestRepository extends PdoRepository implements ApprovalRequestRepositoryInterface
{
    public function __construct(private readonly ApprovalStepRepositoryInterface $steps, \PDO $connection)
    {
        parent::__construct($connection);
    }

    public function findByObject(string $objectType, string $objectId, string $companyId): ?ApprovalRequest
    {
        return $this->guard(function () use ($objectType, $objectId, $companyId) {
            $statement = $this->connection->prepare('SELECT id, company_id, object_type, object_id, status, created_by_company_user_id FROM approval_requests WHERE object_type = :object_type AND object_id = :object_id AND company_id = :company_id LIMIT 1');
            $statement->bindValue(':object_type', $objectType);
            $statement->bindValue(':object_id', $objectId);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function findWithSteps(string $requestId, string $companyId): ?ApprovalRequest
    {
        return $this->guard(function () use ($requestId, $companyId) {
            $statement = $this->connection->prepare('SELECT id, company_id, object_type, object_id, status, created_by_company_user_id FROM approval_requests WHERE id = :id AND company_id = :company_id');
            $statement->bindValue(':id', $requestId);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();
            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            $steps = $this->steps->listByRequest($requestId);

            return new ApprovalRequest(
                id: (string) $row['id'],
                companyId: (string) $row['company_id'],
                objectType: (string) $row['object_type'],
                objectId: (string) $row['object_id'],
                status: (string) $row['status'],
                createdByCompanyUserId: $row['created_by_company_user_id'] !== null ? (string) $row['created_by_company_user_id'] : null,
                steps: $steps,
            );
        });
    }

    public function create(array $data): ApprovalRequest
    {
        return $this->guard(function () use ($data) {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO approval_requests (id, company_id, object_type, object_id, status, created_by_company_user_id)
VALUES (:id, :company_id, :object_type, :object_id, :status, :created_by_company_user_id)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':object_type', $data['object_type']);
            $statement->bindValue(':object_id', $data['object_id']);
            $statement->bindValue(':status', $data['status']);
            $statement->bindValue(':created_by_company_user_id', $data['created_by_company_user_id']);
            $statement->execute();

            $created = $this->findWithSteps($id, $data['company_id']);
            if ($created === null) {
                throw new \RuntimeException('No se pudo crear la solicitud.');
            }

            return $created;
        });
    }

    public function updateStatus(string $requestId, string $companyId, string $status): ApprovalRequest
    {
        return $this->guard(function () use ($requestId, $companyId, $status) {
            $statement = $this->connection->prepare('UPDATE approval_requests SET status = :status WHERE id = :id AND company_id = :company_id');
            $statement->bindValue(':status', $status);
            $statement->bindValue(':id', $requestId);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            $updated = $this->findWithSteps($requestId, $companyId);
            if ($updated === null) {
                throw new \RuntimeException('No se encontró la solicitud de aprobación.');
            }

            return $updated;
        });
    }

    private function hydrate(array $row): ApprovalRequest
    {
        return new ApprovalRequest(
            id: (string) $row['id'],
            companyId: (string) $row['company_id'],
            objectType: (string) $row['object_type'],
            objectId: (string) $row['object_id'],
            status: (string) $row['status'],
            createdByCompanyUserId: $row['created_by_company_user_id'] !== null ? (string) $row['created_by_company_user_id'] : null,
            steps: [],
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
