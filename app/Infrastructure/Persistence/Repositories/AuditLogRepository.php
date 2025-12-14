<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\AuditLogRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class AuditLogRepository extends PdoRepository implements AuditLogRepositoryInterface
{
    public function record(array $data): void
    {
        $this->guard(function () use ($data) {
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO audit_logs (id, company_id, actor_user_id, actor_company_user_id, action, object_type, object_id, ip, user_agent, metadata)
VALUES (:id, :company_id, :actor_user_id, :actor_company_user_id, :action, :object_type, :object_id, :ip, :user_agent, :metadata)
SQL);

            $statement->bindValue(':id', $this->uuid());
            $statement->bindValue(':company_id', $data['company_id'] ?? null);
            $statement->bindValue(':actor_user_id', $data['actor_user_id']);
            $statement->bindValue(':actor_company_user_id', $data['actor_company_user_id'] ?? null);
            $statement->bindValue(':action', $data['action']);
            $statement->bindValue(':object_type', $data['object_type'] ?? null);
            $statement->bindValue(':object_id', $data['object_id'] ?? null);
            $statement->bindValue(':ip', $data['ip'] ?? null);
            $statement->bindValue(':user_agent', $data['user_agent'] ?? null);
            $statement->bindValue(':metadata', json_encode($data['metadata'] ?? [], JSON_THROW_ON_ERROR));
            $statement->execute();
        });
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }
}
