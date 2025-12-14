<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\CompanySettings;

interface CompanySettingsRepositoryInterface
{
    public function getByCompanyId(string $companyId): CompanySettings;

    /**
     * @param array{tax_id?:string|null,tax_regime?:string|null,billing_address?:string|null,invoice_series?:string|null,invoice_number_next?:int,default_language?:string} $settings
     */
    public function upsert(string $companyId, array $settings): CompanySettings;
}
