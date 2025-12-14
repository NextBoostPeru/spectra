<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use RuntimeException;

final class AddIndexesAndConstraints extends AbstractMigration
{
    public function up(): void
    {
        $this->execute($this->loadSql(__DIR__ . '/../sql/01_indexes_constraints.sql'));
    }

    public function down(): void
    {
        // Los índices y llaves foráneas se eliminan junto con las tablas base.
        // No se requiere acción adicional aquí.
    }

    private function loadSql(string $path): string
    {
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException(sprintf('No se pudo leer el archivo SQL: %s', $path));
        }

        return $sql;
    }
}
