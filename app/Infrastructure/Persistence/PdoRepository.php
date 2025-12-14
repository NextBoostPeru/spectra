<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use PDOException;
use App\Application\Exceptions\ApplicationException;

/**
 * Implementación base de repositorios sobre PDO.
 */
abstract class PdoRepository
{
    public function __construct(protected PDO $connection)
    {
    }

    /**
     * Ejecuta una acción encerrándola en manejo de errores consistente.
     */
    protected function guard(callable $action): mixed
    {
        try {
            return $action();
        } catch (PDOException $exception) {
            throw new ApplicationException('Error de persistencia: ' . $exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Añade un filtro de soft-delete a una consulta base.
     * Úsalo únicamente en tablas que incluyan una columna `deleted_at`.
     */
    protected function withSoftDeleteScope(string $query, ?string $alias = null): string
    {
        $column = $alias !== null && $alias !== ''
            ? sprintf('%s.deleted_at', $alias)
            : 'deleted_at';

        $normalized = rtrim($query);
        $hasWhere = stripos($normalized, ' where ') !== false;

        return sprintf('%s %s %s IS NULL', $normalized, $hasWhere ? 'AND' : 'WHERE', $column);
    }

    /**
     * Añade un filtro obligatorio por company_id a consultas multi-tenant.
     */
    protected function withCompanyScope(string $query, string $companyId, ?string $alias = null, string $column = 'company_id'): string
    {
        $columnName = $alias !== null && $alias !== ''
            ? sprintf('%s.%s', $alias, $column)
            : $column;

        $normalized = rtrim($query);
        $hasWhere = stripos($normalized, ' where ') !== false;

        return sprintf('%s %s %s = %s', $normalized, $hasWhere ? 'AND' : 'WHERE', $columnName, $this->connection->quote($companyId));
    }
}
