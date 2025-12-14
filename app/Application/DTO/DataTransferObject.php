<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * DTO inmutable para mover datos entre capas sin exponer detalles de transporte.
 */
abstract class DataTransferObject
{
    /**
     * @param array<string, mixed> $payload
     */
    final public function __construct(protected array $payload)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
