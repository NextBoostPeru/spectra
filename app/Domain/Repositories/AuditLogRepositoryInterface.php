<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface AuditLogRepositoryInterface
{
    /**
     * @param array{company_id?:string|null,actor_user_id:string,actor_company_user_id?:string|null,action:string,object_type?:string|null,object_id?:string|null,ip?:string|null,user_agent?:string|null,metadata?:array<string,mixed>} $data
     */
    public function record(array $data): void;
}
