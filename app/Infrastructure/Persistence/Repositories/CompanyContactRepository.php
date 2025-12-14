<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\CompanyContact;
use App\Domain\Repositories\CompanyContactRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class CompanyContactRepository extends PdoRepository implements CompanyContactRepositoryInterface
{
    public function listForCompany(string $companyId): array
    {
        return $this->guard(function () use ($companyId) {
            $statement = $this->connection->prepare('SELECT id, company_id, name, email, phone, type, is_primary FROM company_contacts WHERE company_id = :company_id ORDER BY is_primary DESC, created_at ASC');
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            return array_map(fn (array $row): CompanyContact => $this->hydrate($row), $statement->fetchAll());
        });
    }

    public function create(string $companyId, array $data): CompanyContact
    {
        return $this->guard(function () use ($companyId, $data) {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO company_contacts (id, company_id, name, email, phone, type, is_primary)
VALUES (:id, :company_id, :name, :email, :phone, :type, :is_primary)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':name', $data['name']);
            $statement->bindValue(':email', strtolower($data['email']));
            $statement->bindValue(':phone', $data['phone'] ?? null);
            $statement->bindValue(':type', $data['type'] ?? 'primary');
            $statement->bindValue(':is_primary', $data['is_primary'] ?? false, \PDO::PARAM_BOOL);
            $statement->execute();

            if (($data['is_primary'] ?? false) === true) {
                $this->demoteOtherPrimary($companyId, $id);
            }

            return $this->findContact($companyId, $id);
        });
    }

    public function update(string $companyId, string $contactId, array $data): CompanyContact
    {
        return $this->guard(function () use ($companyId, $contactId, $data) {
            $fields = [];
            $bindings = [
                ':id' => $contactId,
                ':company_id' => $companyId,
            ];

            foreach (['name', 'email', 'phone', 'type'] as $column) {
                if (array_key_exists($column, $data)) {
                    $fields[] = sprintf('%s = :%s', $column, $column);
                    $bindings[':' . $column] = $column === 'email'
                        ? strtolower((string) $data[$column])
                        : $data[$column];
                }
            }

            if (array_key_exists('is_primary', $data)) {
                $fields[] = 'is_primary = :is_primary';
                $bindings[':is_primary'] = (bool) $data['is_primary'];
            }

            if ($fields !== []) {
                $query = sprintf('UPDATE company_contacts SET %s WHERE id = :id AND company_id = :company_id', implode(', ', $fields));
                $statement = $this->connection->prepare($query);

                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value, $key === ':is_primary' ? \PDO::PARAM_BOOL : \PDO::PARAM_STR);
                }

                $statement->execute();

                if (($data['is_primary'] ?? false) === true) {
                    $this->demoteOtherPrimary($companyId, $contactId);
                }
            }

            return $this->findContact($companyId, $contactId);
        });
    }

    public function delete(string $companyId, string $contactId): void
    {
        $this->guard(function () use ($companyId, $contactId) {
            $statement = $this->connection->prepare('DELETE FROM company_contacts WHERE id = :id AND company_id = :company_id');
            $statement->bindValue(':id', $contactId);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();
        });
    }

    private function findContact(string $companyId, string $contactId): CompanyContact
    {
        $statement = $this->connection->prepare('SELECT id, company_id, name, email, phone, type, is_primary FROM company_contacts WHERE id = :id AND company_id = :company_id');
        $statement->bindValue(':id', $contactId);
        $statement->bindValue(':company_id', $companyId);
        $statement->execute();

        $row = $statement->fetch();

        if ($row === false) {
            throw new \RuntimeException('Contacto no encontrado');
        }

        return $this->hydrate($row);
    }

    private function demoteOtherPrimary(string $companyId, string $keepContactId): void
    {
        $statement = $this->connection->prepare('UPDATE company_contacts SET is_primary = 0 WHERE company_id = :company_id AND id <> :id');
        $statement->bindValue(':company_id', $companyId);
        $statement->bindValue(':id', $keepContactId);
        $statement->execute();
    }

    private function hydrate(array $row): CompanyContact
    {
        return new CompanyContact(
            id: (string) $row['id'],
            companyId: (string) $row['company_id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            phone: $row['phone'] !== null ? (string) $row['phone'] : null,
            type: (string) $row['type'],
            isPrimary: (bool) $row['is_primary'],
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
