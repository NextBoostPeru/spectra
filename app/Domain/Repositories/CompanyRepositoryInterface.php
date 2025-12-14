<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Company;

interface CompanyRepositoryInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function paginate(int $page, int $pageSize): array;

    public function count(): int;

    public function findById(string $id): ?Company;

    /**
     * @param array{legal_name:string,trade_name?:string|null,country_id:int,default_currency_id:int,timezone:string,status?:string} $data
     */
    public function create(array $data): Company;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Company;

    public function softDelete(string $id): void;
}
