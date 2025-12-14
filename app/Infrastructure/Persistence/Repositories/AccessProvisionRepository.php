<?php

declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\AccessProvision;
use App\Domain\Repositories\AccessProvisionRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class AccessProvisionRepository extends PdoRepository implements AccessProvisionRepositoryInterface
{
    public function create(array $data): AccessProvision
    {
        return $this->guard(function () use ($data): AccessProvision {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO onboarding_access_provisions (id, assignment_item_id, system_name, resource, access_level, account_identifier, granted_by_company_user_id, granted_at, notes)
VALUES (:id, :assignment_item_id, :system_name, :resource, :access_level, :account_identifier, :granted_by_company_user_id, :granted_at, :notes)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':assignment_item_id', $data['assignment_item_id']);
            $statement->bindValue(':system_name', $data['system_name']);
            $statement->bindValue(':resource', $data['resource'] ?? null);
            $statement->bindValue(':access_level', $data['access_level'] ?? null);
            $statement->bindValue(':account_identifier', $data['account_identifier'] ?? null);
            $statement->bindValue(':granted_by_company_user_id', $data['granted_by_company_user_id'] ?? null);
            $statement->bindValue(':granted_at', $data['granted_at'] ?? null);
            $statement->bindValue(':notes', $data['notes'] ?? null);
            $statement->execute();

            return new AccessProvision(
                id: $id,
                assignmentItemId: $data['assignment_item_id'],
                systemName: $data['system_name'],
                resource: $data['resource'] ?? null,
                accessLevel: $data['access_level'] ?? null,
                accountIdentifier: $data['account_identifier'] ?? null,
                grantedByCompanyUserId: $data['granted_by_company_user_id'] ?? null,
                grantedAt: $data['granted_at'] ?? null,
                notes: $data['notes'] ?? null,
            );
        });
    }

    public function listByAssignmentItem(string $assignmentItemId): array
    {
        return $this->guard(function () use ($assignmentItemId): array {
            $statement = $this->connection->prepare('SELECT id, assignment_item_id, system_name, resource, access_level, account_identifier, granted_by_company_user_id, granted_at, notes FROM onboarding_access_provisions WHERE assignment_item_id = :assignment_item_id');
            $statement->bindValue(':assignment_item_id', $assignmentItemId);
            $statement->execute();
            $rows = $statement->fetchAll();
            $provisions = [];
            foreach ($rows as $row) {
                $provisions[] = new AccessProvision(
                    id: (string) $row['id'],
                    assignmentItemId: (string) $row['assignment_item_id'],
                    systemName: (string) $row['system_name'],
                    resource: $row['resource'] !== null ? (string) $row['resource'] : null,
                    accessLevel: $row['access_level'] !== null ? (string) $row['access_level'] : null,
                    accountIdentifier: $row['account_identifier'] !== null ? (string) $row['account_identifier'] : null,
                    grantedByCompanyUserId: $row['granted_by_company_user_id'] !== null ? (string) $row['granted_by_company_user_id'] : null,
                    grantedAt: $row['granted_at'] !== null ? (string) $row['granted_at'] : null,
                    notes: $row['notes'] !== null ? (string) $row['notes'] : null,
                );
            }

            return $provisions;
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
