<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\ContractSigner;
use App\Domain\Repositories\ContractSignerRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ContractSignerRepository extends PdoRepository implements ContractSignerRepositoryInterface
{
    public function create(array $data): ContractSigner
    {
        return $this->guard(function () use ($data): ContractSigner {
            $id = $data['id'] ?? $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO contract_signers (id, contract_version_id, role, name, email, signer_type, signer_id, docusign_recipient_id, status, signed_at)
VALUES (:id, :contract_version_id, :role, :name, :email, :signer_type, :signer_id, :docusign_recipient_id, :status, :signed_at)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':contract_version_id', $data['contract_version_id']);
            $statement->bindValue(':role', $data['role']);
            $statement->bindValue(':name', $data['name']);
            $statement->bindValue(':email', $data['email']);
            $statement->bindValue(':signer_type', $data['signer_type'] ?? null);
            $statement->bindValue(':signer_id', $data['signer_id'] ?? null);
            $statement->bindValue(':docusign_recipient_id', $data['docusign_recipient_id'] ?? null);
            $statement->bindValue(':status', $data['status'] ?? 'pending');
            $statement->bindValue(':signed_at', $data['signed_at'] ?? null);
            $statement->execute();

            return $this->update($id, []);
        });
    }

    public function replaceForVersion(string $contractVersionId, array $signers): array
    {
        return $this->guard(function () use ($contractVersionId, $signers): array {
            $deleteStatement = $this->connection->prepare('DELETE FROM contract_signers WHERE contract_version_id = :version_id');
            $deleteStatement->bindValue(':version_id', $contractVersionId);
            $deleteStatement->execute();

            $created = [];
            foreach ($signers as $signer) {
                $created[] = $this->create(array_merge($signer, ['contract_version_id' => $contractVersionId]));
            }

            return $created;
        });
    }

    public function update(string $id, array $data): ContractSigner
    {
        return $this->guard(function () use ($id, $data): ContractSigner {
            if ($data !== []) {
                $fields = [];
                $bindings = [':id' => $id];

                foreach ($data as $column => $value) {
                    $fields[] = sprintf('%s = :%s', $column, $column);
                    $bindings[':' . $column] = $value;
                }

                $statement = $this->connection->prepare(sprintf('UPDATE contract_signers SET %s WHERE id = :id', implode(', ', $fields)));

                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value);
                }

                $statement->execute();
            }

            $statement = $this->connection->prepare('SELECT * FROM contract_signers WHERE id = :id');
            $statement->bindValue(':id', $id);
            $statement->execute();

            $row = $statement->fetch();
            $signedAt = null;
            if (! empty($row['signed_at'])) {
                $signedAt = new DateTimeImmutable((string) $row['signed_at']);
            }

            return new ContractSigner(
                id: (string) $row['id'],
                contractVersionId: (string) $row['contract_version_id'],
                role: (string) $row['role'],
                name: (string) $row['name'],
                email: (string) $row['email'],
                signerType: $row['signer_type'] !== null ? (string) $row['signer_type'] : null,
                signerId: $row['signer_id'] !== null ? (string) $row['signer_id'] : null,
                docusignRecipientId: $row['docusign_recipient_id'] !== null ? (string) $row['docusign_recipient_id'] : null,
                status: (string) $row['status'],
                signedAt: $signedAt,
            );
        });
    }

    public function byVersion(string $contractVersionId): array
    {
        return $this->guard(function () use ($contractVersionId): array {
            $statement = $this->connection->prepare('SELECT * FROM contract_signers WHERE contract_version_id = :version_id');
            $statement->bindValue(':version_id', $contractVersionId);
            $statement->execute();

            $rows = $statement->fetchAll();
            $signers = [];

            foreach ($rows as $row) {
                $signedAt = null;
                if (! empty($row['signed_at'])) {
                    $signedAt = new DateTimeImmutable((string) $row['signed_at']);
                }

                $signers[] = new ContractSigner(
                    id: (string) $row['id'],
                    contractVersionId: (string) $row['contract_version_id'],
                    role: (string) $row['role'],
                    name: (string) $row['name'],
                    email: (string) $row['email'],
                    signerType: $row['signer_type'] !== null ? (string) $row['signer_type'] : null,
                    signerId: $row['signer_id'] !== null ? (string) $row['signer_id'] : null,
                    docusignRecipientId: $row['docusign_recipient_id'] !== null ? (string) $row['docusign_recipient_id'] : null,
                    status: (string) $row['status'],
                    signedAt: $signedAt,
                );
            }

            return $signers;
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
