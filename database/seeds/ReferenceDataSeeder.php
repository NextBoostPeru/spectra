<?php

declare(strict_types=1);

use DateTimeImmutable;
use Phinx\Seed\AbstractSeed;

final class ReferenceDataSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->upsert('countries', [
            ['id' => 840, 'iso2' => 'US', 'name' => 'United States'],
            ['id' => 484, 'iso2' => 'MX', 'name' => 'Mexico'],
            ['id' => 170, 'iso2' => 'CO', 'name' => 'Colombia'],
        ], ['name']);

        $this->upsert('currencies', [
            ['id' => 840, 'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['id' => 484, 'code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$'],
            ['id' => 170, 'code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$'],
        ], ['name', 'symbol']);

        $permissions = [
            ['code' => 'platform.admin', 'description' => 'Administrar la plataforma completa', 'scope' => 'platform'],
            ['code' => 'users.manage', 'description' => 'Gestionar usuarios y accesos', 'scope' => 'company'],
            ['code' => 'roles.manage', 'description' => 'Administrar roles y permisos', 'scope' => 'company'],
            ['code' => 'projects.manage', 'description' => 'Administrar proyectos y asignaciones', 'scope' => 'company'],
            ['code' => 'purchases.manage', 'description' => 'Gestionar requisiciones y órdenes de compra', 'scope' => 'company'],
            ['code' => 'payroll.run', 'description' => 'Ejecutar nómina y pagos', 'scope' => 'company'],
            ['code' => 'support.manage', 'description' => 'Atender tickets de soporte', 'scope' => 'company'],
        ];

        $permissionIds = [];
        foreach ($permissions as $permission) {
            $row = [
                'id' => $this->uuidFromSeed($permission['code']),
                'code' => $permission['code'],
                'description' => $permission['description'],
                'scope' => $permission['scope'],
            ];
            $this->upsert('permissions', [$row], ['description', 'scope']);
            $permissionIds[$permission['code']] = $row['id'];
        }

        $companies = $this->fetchAll('SELECT id FROM companies');

        if (! $companies) {
            return;
        }

        foreach ($companies as $company) {
            $companyId = $company['id'];
            $roleTemplates = [
                'owner' => [
                    'name' => 'Owner',
                    'permissions' => [
                        'platform.admin',
                        'users.manage',
                        'roles.manage',
                        'projects.manage',
                        'purchases.manage',
                        'payroll.run',
                    ],
                ],
                'approver' => [
                    'name' => 'Approver',
                    'permissions' => [
                        'projects.manage',
                        'purchases.manage',
                    ],
                ],
                'viewer' => [
                    'name' => 'Viewer',
                    'permissions' => ['projects.manage'],
                ],
                'support' => [
                    'name' => 'Support',
                    'permissions' => ['support.manage'],
                ],
            ];

            foreach ($roleTemplates as $key => $template) {
                $roleId = $this->uuidFromSeed($companyId . ':' . $key);
                $roleData = [
                    'id' => $roleId,
                    'company_id' => $companyId,
                    'name' => $template['name'],
                    'is_system' => 1,
                    'created_at' => $now,
                ];

                $this->upsert('roles', [$roleData], ['name']);

                foreach ($template['permissions'] as $code) {
                    if (! isset($permissionIds[$code])) {
                        continue;
                    }

                    $link = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionIds[$code],
                    ];

                    $this->upsert('role_permissions', [$link], []);
                }
            }
        }
    }

    private function upsert(string $table, array $rows, array $updateColumns): void
    {
        $connection = $this->getAdapter()->getConnection();

        foreach ($rows as $row) {
            $columns = array_keys($row);
            $values = array_map(static fn ($value): string => $connection->quote((string) $value), $row);
            $updates = $updateColumns === []
                ? ['id = id']
                : array_map(static fn ($column): string => sprintf('`%s` = VALUES(`%s`)', $column, $column), $updateColumns);

            $sql = sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
                $table,
                '`' . implode('`,`', $columns) . '`',
                implode(',', $values),
                implode(',', $updates)
            );

            $this->execute($sql);
        }
    }

    private function uuidFromSeed(string $seed): string
    {
        $hash = md5($seed);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
