<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\ProjectMemberRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;
use App\Domain\Entities\ProjectMember;

class ProjectMemberRepository extends PdoRepository implements ProjectMemberRepositoryInterface
{
    public function isMember(string $projectId, string $companyUserId): bool
    {
        return $this->guard(function () use ($projectId, $companyUserId): bool {
            $query = <<<'SQL'
SELECT 1
FROM project_members pm
INNER JOIN projects p ON p.id = pm.project_id
INNER JOIN company_users cu ON cu.id = pm.company_user_id
WHERE pm.project_id = :project_id
  AND pm.company_user_id = :company_user_id
SQL;

            $query = $this->withSoftDeleteScope($query, 'p');
            $query = $this->withSoftDeleteScope($query, 'cu');
            $query .= ' LIMIT 1';

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':project_id', $projectId);
            $statement->bindValue(':company_user_id', $companyUserId);
            $statement->execute();

            return $statement->fetchColumn() !== false;
        });
    }

    public function addMember(string $projectId, string $companyUserId, string $role): ProjectMember
    {
        return $this->guard(function () use ($projectId, $companyUserId, $role): ProjectMember {
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO project_members (project_id, company_user_id, role_in_project)
VALUES (:project_id, :company_user_id, :role_in_project)
ON DUPLICATE KEY UPDATE role_in_project = VALUES(role_in_project)
SQL);

            $statement->bindValue(':project_id', $projectId);
            $statement->bindValue(':company_user_id', $companyUserId);
            $statement->bindValue(':role_in_project', $role);
            $statement->execute();

            return new ProjectMember($projectId, $companyUserId, $role);
        });
    }

    public function listByProject(string $projectId, string $companyId): array
    {
        return $this->guard(function () use ($projectId, $companyId): array {
            $query = <<<'SQL'
SELECT pm.project_id, pm.company_user_id, pm.role_in_project
FROM project_members pm
INNER JOIN projects p ON p.id = pm.project_id
WHERE pm.project_id = :project_id
SQL;

            $query = $this->withCompanyScope($query, $companyId, 'p');
            $query = $this->withSoftDeleteScope($query, 'p');

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':project_id', $projectId);
            $statement->execute();

            $rows = $statement->fetchAll();

            return array_map(
                static fn (array $row): ProjectMember => new ProjectMember(
                    (string) $row['project_id'],
                    (string) $row['company_user_id'],
                    (string) $row['role_in_project'],
                ),
                $rows,
            );
        });
    }
}
