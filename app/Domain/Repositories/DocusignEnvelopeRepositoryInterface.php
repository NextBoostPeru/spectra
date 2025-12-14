<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface DocusignEnvelopeRepositoryInterface
{
    /**
     * @param array{id?:string,contract_id:string,contract_version_id?:string|null,envelope_id:string,status?:string|null,webhook_key?:string|null,payload?:array|null,last_event_at?:string|null} $data
     */
    public function create(array $data): void;

    /**
     * @param array<string, scalar|array|null> $data
     */
    public function updateByEnvelopeId(string $envelopeId, array $data): void;
}
