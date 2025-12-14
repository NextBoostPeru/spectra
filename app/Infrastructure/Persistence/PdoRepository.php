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
}
