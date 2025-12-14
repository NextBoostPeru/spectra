<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\PermissionRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class PermissionRepository extends PdoRepository implements PermissionRepositoryInterface
{
    public function getPermissionsForCompanyUser(string $companyUserId): array
    {
        return $this->guard(function () use ($companyUserId) {
            $query = <<<'SQL'
SELECT DISTINCT p.code
FROM user_roles ur
INNER JOIN company_users cu ON cu.id = ur.company_user_id
INNER JOIN roles r ON r.id = ur.role_id AND r.company_id = cu.company_id
INNER JOIN role_permissions rp ON rp.role_id = r.id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE ur.company_user_id = :company_user_id
SQL;

            $query = $this->withSoftDeleteScope($query, 'cu');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':company_user_id', $companyUserId);
            $statement->execute();

            return array_map(static fn (array $row): string => (string) $row['code'], $statement->fetchAll());
        });
    }
}
