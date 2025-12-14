<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\ApprovalStep;
use App\Domain\Repositories\ApprovalStepRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ApprovalStepRepository extends PdoRepository implements ApprovalStepRepositoryInterface
{
    public function bulkCreate(array $steps): void
    {
        $this->guard(function () use ($steps) {
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO approval_steps (id, approval_request_id, sequence_order, required_role_id, assigned_to_company_user_id, status, acted_by_company_user_id, acted_at, comment)
VALUES (:id, :approval_request_id, :sequence_order, :required_role_id, :assigned_to_company_user_id, :status, :acted_by_company_user_id, :acted_at, :comment)
SQL);

            foreach ($steps as $step) {
                $statement->bindValue(':id', $step['id']);
                $statement->bindValue(':approval_request_id', $step['approval_request_id']);
                $statement->bindValue(':sequence_order', $step['sequence_order']);
                $statement->bindValue(':required_role_id', $step['required_role_id']);
                $statement->bindValue(':assigned_to_company_user_id', $step['assigned_to_company_user_id']);
                $statement->bindValue(':status', $step['status']);
                $statement->bindValue(':acted_by_company_user_id', $step['acted_by_company_user_id']);
                $statement->bindValue(':acted_at', $step['acted_at']);
                $statement->bindValue(':comment', $step['comment']);
                $statement->execute();
            }
        });
    }

    public function listByRequest(string $requestId): array
    {
        return $this->guard(function () use ($requestId) {
            $statement = $this->connection->prepare('SELECT id, approval_request_id, sequence_order, required_role_id, assigned_to_company_user_id, status, acted_by_company_user_id, acted_at, comment FROM approval_steps WHERE approval_request_id = :requestId ORDER BY sequence_order ASC');
            $statement->bindValue(':requestId', $requestId);
            $statement->execute();

            $rows = $statement->fetchAll();

            return array_map(fn ($row) => $this->hydrate($row), $rows);
        });
    }

    public function update(string $stepId, string $requestId, array $data): ApprovalStep
    {
        return $this->guard(function () use ($stepId, $requestId, $data) {
            $fields = [];
            $bindings = [
                ':id' => $stepId,
                ':request_id' => $requestId,
            ];

            foreach (['sequence_order', 'required_role_id', 'assigned_to_company_user_id', 'status', 'acted_by_company_user_id', 'acted_at', 'comment'] as $column) {
                if (array_key_exists($column, $data)) {
                    $fields[] = sprintf('%s = :%s', $column, $column);
                    $bindings[':' . $column] = $data[$column];
                }
            }

            if ($fields !== []) {
                $statement = $this->connection->prepare(sprintf('UPDATE approval_steps SET %s WHERE id = :id AND approval_request_id = :request_id', implode(', ', $fields)));
                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value);
                }
                $statement->execute();
            }

            $updated = $this->listByRequest($requestId);
            foreach ($updated as $step) {
                if ($step->id() === $stepId) {
                    return $step;
                }
            }

            throw new \RuntimeException('No se pudo actualizar el paso.');
        });
    }

    private function hydrate(array $row): ApprovalStep
    {
        return new ApprovalStep(
            id: (string) $row['id'],
            approvalRequestId: (string) $row['approval_request_id'],
            sequenceOrder: (int) $row['sequence_order'],
            requiredRoleId: (string) $row['required_role_id'],
            assignedToCompanyUserId: $row['assigned_to_company_user_id'] !== null ? (string) $row['assigned_to_company_user_id'] : null,
            status: (string) $row['status'],
            actedByCompanyUserId: $row['acted_by_company_user_id'] !== null ? (string) $row['acted_by_company_user_id'] : null,
            actedAt: $row['acted_at'] !== null ? (string) $row['acted_at'] : null,
            comment: $row['comment'] !== null ? (string) $row['comment'] : null,
        );
    }
}
