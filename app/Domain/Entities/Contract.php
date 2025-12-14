<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Contract
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $freelancerId,
        private readonly string $templateId,
        private readonly int $jurisdictionCountryId,
        private readonly string $paymentType,
        private readonly int $rateCurrencyId,
        private readonly ?string $projectId = null,
        private readonly ?string $title = null,
        private readonly ?string $counterpartyName = null,
        private readonly ?string $counterpartyEmail = null,
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
        private readonly int $noticeDays = 0,
        private readonly ?float $rateAmount = null,
        private readonly ?float $retainerAmount = null,
        private readonly ?string $currentVersionId = null,
        private readonly string $status = 'draft',
        private readonly ?string $legalApprovedByCompanyUserId = null,
        private readonly ?\DateTimeImmutable $legalApprovedAt = null,
        private readonly ?\DateTimeImmutable $lastExpirationNotifiedAt = null,
        private readonly ?\DateTimeImmutable $deletedAt = null,
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

    public function status(): string
    {
        return $this->status;
    }

    public function endDate(): ?string
    {
        return $this->endDate;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'project_id' => $this->projectId,
            'freelancer_id' => $this->freelancerId,
            'template_id' => $this->templateId,
            'jurisdiction_country_id' => $this->jurisdictionCountryId,
            'title' => $this->title,
            'counterparty_name' => $this->counterpartyName,
            'counterparty_email' => $this->counterpartyEmail,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'notice_days' => $this->noticeDays,
            'payment_type' => $this->paymentType,
            'rate_amount' => $this->rateAmount,
            'rate_currency_id' => $this->rateCurrencyId,
            'retainer_amount' => $this->retainerAmount,
            'current_version_id' => $this->currentVersionId,
            'status' => $this->status,
            'legal_approved_by_company_user_id' => $this->legalApprovedByCompanyUserId,
            'legal_approved_at' => $this->legalApprovedAt?->format(DATE_ATOM),
            'last_expiration_notified_at' => $this->lastExpirationNotifiedAt?->format(DATE_ATOM),
        ];
    }
}
