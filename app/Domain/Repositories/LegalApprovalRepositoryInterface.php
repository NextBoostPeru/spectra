<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface LegalApprovalRepositoryInterface
{
    /**
     * @param array{id?:string,company_id:string,contract_id:string,contract_version_id?:string|null,status:string,reviewed_by_company_user_id?:string|null,reviewed_at?:string|null,comment?:string|null} $data
     */
    public function create(array $data): void;

    /**
     * @param array<string, scalar|null> $data
     */
    public function update(string $id, array $data): void;
}
