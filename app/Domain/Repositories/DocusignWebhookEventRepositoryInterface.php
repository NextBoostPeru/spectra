<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface DocusignWebhookEventRepositoryInterface
{
    /**
     * @param array{id?:string,envelope_id:string,contract_version_id?:string|null,event_type:string,status?:string|null,signature_valid:bool,payload?:array|null} $data
     */
    public function create(array $data): void;
}
