<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\ProjectMemberRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

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
}
