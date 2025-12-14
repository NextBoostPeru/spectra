<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\CompanyContact;

interface CompanyContactRepositoryInterface
{
    /**
     * @return list<CompanyContact>
     */
    public function listForCompany(string $companyId): array;

    /**
     * @param array{name:string,email:string,phone?:string|null,type?:string,is_primary?:bool} $data
     */
    public function create(string $companyId, array $data): CompanyContact;

    /**
     * @param array{name?:string,email?:string,phone?:string|null,type?:string,is_primary?:bool} $data
     */
    public function update(string $companyId, string $contactId, array $data): CompanyContact;

    public function delete(string $companyId, string $contactId): void;
}
