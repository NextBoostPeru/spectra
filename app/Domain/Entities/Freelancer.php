<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Freelancer
{
    public function __construct(
        private readonly string $id,
        private readonly string $fullName,
        private readonly string $email,
        private readonly string $status,
        private readonly ?\DateTimeImmutable $deletedAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'status' => $this->status,
        ];
    }
}
