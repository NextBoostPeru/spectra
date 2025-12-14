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

        $permissionGroups = [
            'platform' => [
                ['code' => 'platform.admin', 'description' => 'Administrar la plataforma completa', 'scope' => 'platform'],
            ],
            'users' => [
                ['code' => 'users.view', 'description' => 'Ver usuarios de la empresa', 'scope' => 'company'],
                ['code' => 'users.manage', 'description' => 'Gestionar usuarios y accesos', 'scope' => 'company'],
                ['code' => 'roles.manage', 'description' => 'Administrar roles y permisos', 'scope' => 'company'],
            ],
            'projects' => [
                ['code' => 'projects.view_all', 'description' => 'Ver todos los proyectos de la empresa', 'scope' => 'company'],
                ['code' => 'projects.manage', 'description' => 'Administrar proyectos y asignaciones', 'scope' => 'company'],
                ['code' => 'projects.assign_members', 'description' => 'Asignar miembros a proyectos', 'scope' => 'company'],
            ],
            'procurement' => [
                ['code' => 'requisitions.submit', 'description' => 'Crear y enviar requisiciones', 'scope' => 'company'],
                ['code' => 'requisitions.approve', 'description' => 'Aprobar o rechazar requisiciones', 'scope' => 'company'],
                ['code' => 'requisitions.view', 'description' => 'Ver requisiciones de la empresa', 'scope' => 'company'],
                ['code' => 'purchase_orders.manage', 'description' => 'Gestionar órdenes de compra', 'scope' => 'company'],
                ['code' => 'vendors.manage', 'description' => 'Administrar proveedores', 'scope' => 'company'],
            ],
            'timesheets' => [
                ['code' => 'timesheets.review', 'description' => 'Revisar partes de tiempo', 'scope' => 'company'],
                ['code' => 'timesheets.approve', 'description' => 'Aprobar partes de tiempo', 'scope' => 'company'],
            ],
            'payroll' => [
                ['code' => 'payroll.run', 'description' => 'Ejecutar nómina y pagos', 'scope' => 'company'],
                ['code' => 'payroll.view_reports', 'description' => 'Ver reportes y pólizas de nómina', 'scope' => 'company'],
            ],
            'support' => [
                ['code' => 'support.view', 'description' => 'Consultar tickets de soporte', 'scope' => 'company'],
                ['code' => 'support.manage', 'description' => 'Atender tickets de soporte', 'scope' => 'company'],
            ],
        ];

        $permissions = [];

        foreach ($permissionGroups as $group => $items) {
            foreach ($items as $permission) {
                $permissions[] = $permission;
            }
        }

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

        $allCompanyPermissions = array_values(array_filter(
            array_column($permissions, 'code'),
            static fn (string $code): bool => str_starts_with($code, 'platform.') === false
        ));

        foreach ($companies as $company) {
            $companyId = $company['id'];
            $roleTemplates = [
                'owner' => [
                    'name' => 'Owner',
                    'permissions' => $allCompanyPermissions,
                ],
                'approver' => [
                    'name' => 'Approver',
                    'permissions' => [
                        'projects.manage',
                        'projects.assign_members',
                        'requisitions.approve',
                        'purchase_orders.manage',
                        'timesheets.approve',
                    ],
                ],
                'viewer' => [
                    'name' => 'Viewer',
                    'permissions' => [
                        'projects.view_all',
                        'requisitions.view',
                        'support.view',
                        'timesheets.review',
                    ],
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
