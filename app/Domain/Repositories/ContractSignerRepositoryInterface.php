<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ContractSigner;

interface ContractSignerRepositoryInterface
{
    /**
     * @param array{id?:string,contract_version_id:string,role:string,name:string,email:string,signer_type?:string|null,signer_id?:string|null,docusign_recipient_id?:string|null,status?:string|null,signed_at?:string|null} $data
     */
    public function create(array $data): ContractSigner;

    /**
     * @param array<int, array<string, scalar|null>> $signers
     *
     * @return ContractSigner[]
     */
    public function replaceForVersion(string $contractVersionId, array $signers): array;

    /**
     * @param array<string, scalar|null> $data
     */
    public function update(string $id, array $data): ContractSigner;

    /** @return ContractSigner[] */
    public function byVersion(string $contractVersionId): array;
}
