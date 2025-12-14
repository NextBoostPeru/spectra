<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\ContractTemplate;
use App\Domain\Repositories\ContractTemplateRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ContractTemplateRepository extends PdoRepository implements ContractTemplateRepositoryInterface
{
    public function create(array $data): ContractTemplate
    {
        return $this->guard(function () use ($data): ContractTemplate {
            $id = $data['id'] ?? $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO contract_templates (id, company_id, type, country_id, language_code, title, body, variables_schema, version, status)
VALUES (:id, :company_id, :type, :country_id, :language_code, :title, :body, :variables_schema, :version, :status)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':type', $data['type']);
            $statement->bindValue(':country_id', $data['country_id']);
            $statement->bindValue(':language_code', $data['language_code'] ?? 'es');
            $statement->bindValue(':title', $data['title']);
            $statement->bindValue(':body', $data['body']);
            $statement->bindValue(':variables_schema', isset($data['variables_schema']) ? json_encode($data['variables_schema']) : null);
            $statement->bindValue(':version', $data['version'] ?? 1, \PDO::PARAM_INT);
            $statement->bindValue(':status', $data['status'] ?? 'active');
            $statement->execute();

            return $this->findById($id, $data['company_id']);
        });
    }

    public function findById(string $templateId, string $companyId): ?ContractTemplate
    {
        return $this->guard(function () use ($templateId, $companyId): ?ContractTemplate {
            $query = 'SELECT id, company_id, type, country_id, language_code, title, body, variables_schema, version, status FROM contract_templates WHERE id = :id';
            $query = $this->withCompanyScope($query, $companyId, 'contract_templates');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $templateId);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            $schema = null;
            if (! empty($row['variables_schema'])) {
                $schema = json_decode((string) $row['variables_schema'], true, 512, JSON_THROW_ON_ERROR);
            }

            return new ContractTemplate(
                id: (string) $row['id'],
                companyId: (string) $row['company_id'],
                type: (string) $row['type'],
                countryId: (int) $row['country_id'],
                languageCode: (string) $row['language_code'],
                title: (string) $row['title'],
                body: (string) $row['body'],
                variablesSchema: $schema,
                version: (int) $row['version'],
                status: (string) $row['status'],
            );
        });
    }

    public function list(string $companyId, int $page, int $pageSize): array
    {
        return $this->guard(function () use ($companyId, $page, $pageSize): array {
            $offset = max(0, ($page - 1) * $pageSize);
            $query = 'SELECT id, company_id, type, country_id, language_code, title, body, variables_schema, version, status FROM contract_templates';
            $query = $this->withCompanyScope($query, $companyId, 'contract_templates');
            $query .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $statement->execute();

            $rows = $statement->fetchAll();
            $templates = [];

            foreach ($rows as $row) {
                $schema = null;
                if (! empty($row['variables_schema'])) {
                    $schema = json_decode((string) $row['variables_schema'], true, 512, JSON_THROW_ON_ERROR);
                }

                $templates[] = new ContractTemplate(
                    id: (string) $row['id'],
                    companyId: (string) $row['company_id'],
                    type: (string) $row['type'],
                    countryId: (int) $row['country_id'],
                    languageCode: (string) $row['language_code'],
                    title: (string) $row['title'],
                    body: (string) $row['body'],
                    variablesSchema: $schema,
                    version: (int) $row['version'],
                    status: (string) $row['status'],
                );
            }

            return $templates;
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
