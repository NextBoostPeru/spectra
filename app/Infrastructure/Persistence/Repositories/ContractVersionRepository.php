<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\ContractVersion;
use App\Domain\Repositories\ContractVersionRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ContractVersionRepository extends PdoRepository implements ContractVersionRepositoryInterface
{
    public function create(array $data): ContractVersion
    {
        return $this->guard(function () use ($data): ContractVersion {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO contract_versions (id, contract_id, template_id, template_version, version_number, body_snapshot, storage_path, document_hash, status, docusign_envelope_id, sent_at, signed_at, expires_at, created_by_company_user_id)
VALUES (:id, :contract_id, :template_id, :template_version, :version_number, :body_snapshot, :storage_path, :document_hash, :status, :docusign_envelope_id, :sent_at, :signed_at, :expires_at, :created_by_company_user_id)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':contract_id', $data['contract_id']);
            $statement->bindValue(':template_id', $data['template_id'] ?? null);
            if (isset($data['template_version'])) {
                $statement->bindValue(':template_version', $data['template_version'], \PDO::PARAM_INT);
            } else {
                $statement->bindValue(':template_version', null, \PDO::PARAM_NULL);
            }
            $statement->bindValue(':version_number', $data['version_number'], \PDO::PARAM_INT);
            $statement->bindValue(':body_snapshot', $data['body_snapshot']);
            $statement->bindValue(':storage_path', $data['storage_path'] ?? null);
            $statement->bindValue(':document_hash', $data['document_hash'] ?? null);
            $statement->bindValue(':status', $data['status'] ?? 'draft');
            $statement->bindValue(':docusign_envelope_id', $data['docusign_envelope_id'] ?? null);
            $statement->bindValue(':sent_at', $data['sent_at'] ?? null);
            $statement->bindValue(':signed_at', $data['signed_at'] ?? null);
            $statement->bindValue(':expires_at', $data['expires_at'] ?? null);
            $statement->bindValue(':created_by_company_user_id', $data['created_by_company_user_id'] ?? null);
            $statement->execute();

            return $this->findById($id);
        });
    }

    public function findById(string $id): ?ContractVersion
    {
        return $this->guard(function () use ($id): ?ContractVersion {
            $statement = $this->connection->prepare('SELECT cv.*, c.company_id FROM contract_versions cv JOIN contracts c ON c.id = cv.contract_id WHERE cv.id = :id');
            $statement->bindValue(':id', $id);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            return $this->mapRow($row);
        });
    }

    public function update(string $id, array $data): ContractVersion
    {
        return $this->guard(function () use ($id, $data): ContractVersion {
            $fields = [];
            $bindings = [':id' => $id];

            foreach ($data as $column => $value) {
                $fields[] = sprintf('%s = :%s', $column, $column);
                $bindings[':' . $column] = $value;
            }

            if ($fields !== []) {
                $statement = $this->connection->prepare(sprintf('UPDATE contract_versions SET %s WHERE id = :id', implode(', ', $fields)));

                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value);
                }

                $statement->execute();
            }

            return $this->findById($id);
        });
    }

    public function latestVersionNumber(string $contractId): int
    {
        return $this->guard(function () use ($contractId): int {
            $statement = $this->connection->prepare('SELECT MAX(version_number) AS max_version FROM contract_versions WHERE contract_id = :contract_id');
            $statement->bindValue(':contract_id', $contractId);
            $statement->execute();

            $row = $statement->fetch();

            return (int) ($row['max_version'] ?? 0);
        });
    }

    public function findByEnvelope(string $envelopeId): ?ContractVersion
    {
        return $this->guard(function () use ($envelopeId): ?ContractVersion {
            $statement = $this->connection->prepare('SELECT cv.*, c.company_id FROM contract_versions cv JOIN contracts c ON c.id = cv.contract_id WHERE cv.docusign_envelope_id = :envelope_id');
            $statement->bindValue(':envelope_id', $envelopeId);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            return $this->mapRow($row);
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): ContractVersion
    {
        $sentAt = null;
        if (! empty($row['sent_at'])) {
            $sentAt = new DateTimeImmutable((string) $row['sent_at']);
        }

        $signedAt = null;
        if (! empty($row['signed_at'])) {
            $signedAt = new DateTimeImmutable((string) $row['signed_at']);
        }

        return new ContractVersion(
            id: (string) $row['id'],
            contractId: (string) $row['contract_id'],
            versionNumber: (int) $row['version_number'],
            bodySnapshot: (string) $row['body_snapshot'],
            companyId: $row['company_id'] ?? null,
            templateId: $row['template_id'] !== null ? (string) $row['template_id'] : null,
            templateVersion: $row['template_version'] !== null ? (int) $row['template_version'] : null,
            storagePath: $row['storage_path'] !== null ? (string) $row['storage_path'] : null,
            documentHash: $row['document_hash'] !== null ? (string) $row['document_hash'] : null,
            status: (string) $row['status'],
            docusignEnvelopeId: $row['docusign_envelope_id'] !== null ? (string) $row['docusign_envelope_id'] : null,
            sentAt: $sentAt,
            signedAt: $signedAt,
            expiresAt: $row['expires_at'] !== null ? (string) $row['expires_at'] : null,
            createdByCompanyUserId: $row['created_by_company_user_id'] !== null ? (string) $row['created_by_company_user_id'] : null,
        );
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
