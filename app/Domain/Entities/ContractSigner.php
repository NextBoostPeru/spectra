<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ContractSigner
{
    public function __construct(
        private readonly string $id,
        private readonly string $contractVersionId,
        private readonly string $role,
        private readonly string $name,
        private readonly string $email,
        private readonly ?string $signerType = null,
        private readonly ?string $signerId = null,
        private readonly ?string $docusignRecipientId = null,
        private readonly string $status = 'pending',
        private readonly ?\DateTimeImmutable $signedAt = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contract_version_id' => $this->contractVersionId,
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'signer_type' => $this->signerType,
            'signer_id' => $this->signerId,
            'docusign_recipient_id' => $this->docusignRecipientId,
            'status' => $this->status,
            'signed_at' => $this->signedAt?->format(DATE_ATOM),
        ];
    }
}
