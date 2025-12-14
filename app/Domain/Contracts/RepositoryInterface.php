<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Definición base para los repositorios persistentes en la capa de Dominio.
 *
 * Las implementaciones concretas viven en Infrastructure y deben traducir
 * errores de transporte a excepciones de Dominio o Aplicación.
 */
interface RepositoryInterface
{
    /**
     * Guarda o actualiza una entidad.
     */
    public function save(object $aggregate): void;

    /**
     * Elimina una entidad por su identificador.
     */
    public function delete(string $id): void;
}
