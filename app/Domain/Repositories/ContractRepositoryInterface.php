<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Contract;

interface ContractRepositoryInterface
{
    /**
     * @param array{company_id:string,freelancer_id:string,template_id:string,jurisdiction_country_id:int,payment_type:string,rate_currency_id:int,project_id?:string|null,title?:string|null,counterparty_name?:string|null,counterparty_email?:string|null,start_date?:string|null,end_date?:string|null,notice_days?:int|null,rate_amount?:float|null,retainer_amount?:float|null} $data
     */
    public function create(array $data): Contract;

    public function findById(string $id, string $companyId): ?Contract;

    /**
     * @param array<string, scalar|null> $data
     */
    public function update(string $id, string $companyId, array $data): Contract;

    /** @return Contract[] */
    public function findExpiringWithin(string $companyId, int $days): array;
}
