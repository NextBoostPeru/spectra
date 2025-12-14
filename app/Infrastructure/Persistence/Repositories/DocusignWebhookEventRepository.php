<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\DocusignWebhookEventRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class DocusignWebhookEventRepository extends PdoRepository implements DocusignWebhookEventRepositoryInterface
{
    public function create(array $data): void
    {
        $this->guard(function () use ($data): void {
            $id = $data['id'] ?? $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO docusign_webhook_events (id, envelope_id, contract_version_id, event_type, status, signature_valid, payload)
VALUES (:id, :envelope_id, :contract_version_id, :event_type, :status, :signature_valid, :payload)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':envelope_id', $data['envelope_id']);
            $statement->bindValue(':contract_version_id', $data['contract_version_id'] ?? null);
            $statement->bindValue(':event_type', $data['event_type']);
            $statement->bindValue(':status', $data['status'] ?? null);
            $statement->bindValue(':signature_valid', $data['signature_valid'] ? 1 : 0, \PDO::PARAM_INT);
            $statement->bindValue(':payload', isset($data['payload']) ? json_encode($data['payload']) : null);
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
