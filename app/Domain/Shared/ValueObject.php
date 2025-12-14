<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Stringable;

/**
 * Proporciona igualdad estructural y serialización segura para Value Objects.
 */
abstract class ValueObject implements Stringable
{
    /**
     * Compara dos Value Objects por valor.
     */
    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /**
     * Convierte el Value Object a un arreglo primitivo.
     *
     * @return array<string, scalar|array|null>
     */
    abstract public function toArray(): array;

    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
