<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Role
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $name,
        private readonly bool $isSystem,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function companyId(): string
    {
        return $this->companyId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }
}
