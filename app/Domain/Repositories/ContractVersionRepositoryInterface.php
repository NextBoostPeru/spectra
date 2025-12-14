<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ContractVersion;

interface ContractVersionRepositoryInterface
{
    /**
     * @param array{contract_id:string,template_id?:string|null,template_version?:int|null,version_number:int,body_snapshot:string,storage_path?:string|null,document_hash?:string|null,status?:string|null,docusign_envelope_id?:string|null,expires_at?:string|null,created_by_company_user_id?:string|null} $data
     */
    public function create(array $data): ContractVersion;

    public function findById(string $id): ?ContractVersion;

    /**
     * @param array<string, scalar|null> $data
     */
    public function update(string $id, array $data): ContractVersion;

    public function latestVersionNumber(string $contractId): int;

    public function findByEnvelope(string $envelopeId): ?ContractVersion;
}
