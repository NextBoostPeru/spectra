<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ContractTemplate
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $type,
        private readonly int $countryId,
        private readonly string $languageCode,
        private readonly string $title,
        private readonly string $body,
        private readonly ?array $variablesSchema,
        private readonly int $version,
        private readonly string $status,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function companyId(): string
    {
        return $this->companyId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'type' => $this->type,
            'country_id' => $this->countryId,
            'language_code' => $this->languageCode,
            'title' => $this->title,
            'body' => $this->body,
            'variables_schema' => $this->variablesSchema,
            'version' => $this->version,
            'status' => $this->status,
        ];
    }
}
