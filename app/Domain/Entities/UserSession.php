<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class UserSession
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $refreshTokenHash,
        private readonly string $status,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?\DateTimeImmutable $revokedAt = null,
        private readonly ?string $lastIp = null,
        private readonly ?string $lastUserAgent = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function refreshTokenHash(): string
    {
        return $this->refreshTokenHash;
    }

    public function lastIp(): ?string
    {
        return $this->lastIp;
    }

    public function lastUserAgent(): ?string
    {
        return $this->lastUserAgent;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->revokedAt === null;
    }

    public function isExpired(int $refreshTtlDays): bool
    {
        $expiresAt = $this->createdAt->modify(sprintf('+%d days', $refreshTtlDays));

        return $expiresAt < new \DateTimeImmutable();
    }
}
