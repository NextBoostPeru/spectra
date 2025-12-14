<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class CompanySettings
{
    public function __construct(
        private readonly string $companyId,
        private readonly ?string $taxId,
        private readonly ?string $taxRegime,
        private readonly ?string $billingAddress,
        private readonly ?string $invoiceSeries,
        private readonly int $invoiceNumberNext,
        private readonly string $defaultLanguage,
    ) {
    }

    public function companyId(): string
    {
        return $this->companyId;
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'tax_id' => $this->taxId,
            'tax_regime' => $this->taxRegime,
            'billing_address' => $this->billingAddress,
            'invoice_series' => $this->invoiceSeries,
            'invoice_number_next' => $this->invoiceNumberNext,
            'default_language' => $this->defaultLanguage,
        ];
    }
}
