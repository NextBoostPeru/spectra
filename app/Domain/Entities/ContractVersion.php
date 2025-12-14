<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ContractVersion
{
    public function __construct(
        private readonly string $id,
        private readonly string $contractId,
        private readonly int $versionNumber,
        private readonly string $bodySnapshot,
        private readonly ?string $companyId = null,
        private readonly ?string $templateId = null,
        private readonly ?int $templateVersion = null,
        private readonly ?string $storagePath = null,
        private readonly ?string $documentHash = null,
        private readonly string $status = 'draft',
        private readonly ?string $docusignEnvelopeId = null,
        private readonly ?\DateTimeImmutable $sentAt = null,
        private readonly ?\DateTimeImmutable $signedAt = null,
        private readonly ?string $expiresAt = null,
        private readonly ?string $createdByCompanyUserId = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function contractId(): string
    {
        return $this->contractId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contractId,
            'version_number' => $this->versionNumber,
            'body_snapshot' => $this->bodySnapshot,
            'company_id' => $this->companyId,
            'template_id' => $this->templateId,
            'template_version' => $this->templateVersion,
            'storage_path' => $this->storagePath,
            'document_hash' => $this->documentHash,
            'status' => $this->status,
            'docusign_envelope_id' => $this->docusignEnvelopeId,
            'sent_at' => $this->sentAt?->format(DATE_ATOM),
            'signed_at' => $this->signedAt?->format(DATE_ATOM),
            'expires_at' => $this->expiresAt,
            'created_by_company_user_id' => $this->createdByCompanyUserId,
        ];
    }
}
