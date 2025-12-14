<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ContractTemplate;

interface ContractTemplateRepositoryInterface
{
    /**
     * @param array{id?:string,company_id:string,type:string,country_id:int,language_code?:string,title:string,body:string,variables_schema?:array|null} $data
     */
    public function create(array $data): ContractTemplate;

    public function findById(string $templateId, string $companyId): ?ContractTemplate;

    /** @return ContractTemplate[] */
    public function list(string $companyId, int $page, int $pageSize): array;
}
