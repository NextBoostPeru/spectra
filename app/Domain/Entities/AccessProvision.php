<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class AccessProvision
{
    public function __construct(
        private readonly string $id,
        private readonly string $assignmentItemId,
        private readonly string $systemName,
        private readonly ?string $resource,
        private readonly ?string $accessLevel,
        private readonly ?string $accountIdentifier,
        private readonly ?string $grantedByCompanyUserId,
        private readonly ?string $grantedAt,
        private readonly ?string $notes = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assignment_item_id' => $this->assignmentItemId,
            'system_name' => $this->systemName,
            'resource' => $this->resource,
            'access_level' => $this->accessLevel,
            'account_identifier' => $this->accountIdentifier,
            'granted_by_company_user_id' => $this->grantedByCompanyUserId,
            'granted_at' => $this->grantedAt,
            'notes' => $this->notes,
        ];
    }
}
