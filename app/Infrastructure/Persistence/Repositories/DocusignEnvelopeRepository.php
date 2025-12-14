<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\DocusignEnvelopeRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class DocusignEnvelopeRepository extends PdoRepository implements DocusignEnvelopeRepositoryInterface
{
    public function create(array $data): void
    {
        $this->guard(function () use ($data): void {
            $id = $data['id'] ?? $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO docusign_envelopes (id, contract_id, contract_version_id, envelope_id, status, last_event_at, payload, webhook_key)
VALUES (:id, :contract_id, :contract_version_id, :envelope_id, :status, :last_event_at, :payload, :webhook_key)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':contract_id', $data['contract_id']);
            $statement->bindValue(':contract_version_id', $data['contract_version_id'] ?? null);
            $statement->bindValue(':envelope_id', $data['envelope_id']);
            $statement->bindValue(':status', $data['status'] ?? 'created');
            $statement->bindValue(':last_event_at', $data['last_event_at'] ?? null);
            $statement->bindValue(':payload', isset($data['payload']) ? json_encode($data['payload']) : null);
            $statement->bindValue(':webhook_key', $data['webhook_key'] ?? null);
            $statement->execute();
        });
    }

    public function updateByEnvelopeId(string $envelopeId, array $data): void
    {
        $this->guard(function () use ($envelopeId, $data): void {
            if ($data === []) {
                return;
            }

            $fields = [];
            $bindings = [':envelope_id' => $envelopeId];

            foreach ($data as $column => $value) {
                $fields[] = sprintf('%s = :%s', $column, $column);
                $bindings[':' . $column] = $column === 'payload' ? json_encode($value) : $value;
            }

            $statement = $this->connection->prepare(sprintf('UPDATE docusign_envelopes SET %s WHERE envelope_id = :envelope_id', implode(', ', $fields)));

            foreach ($bindings as $key => $value) {
                $statement->bindValue($key, $value);
            }

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
