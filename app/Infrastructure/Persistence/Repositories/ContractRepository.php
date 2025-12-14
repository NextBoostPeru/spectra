<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\Contract;
use App\Domain\Repositories\ContractRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ContractRepository extends PdoRepository implements ContractRepositoryInterface
{
    public function create(array $data): Contract
    {
        return $this->guard(function () use ($data): Contract {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO contracts (id, company_id, project_id, freelancer_id, template_id, jurisdiction_country_id, title, counterparty_name, counterparty_email, start_date, end_date, notice_days, payment_type, rate_amount, rate_currency_id, retainer_amount, status)
VALUES (:id, :company_id, :project_id, :freelancer_id, :template_id, :jurisdiction_country_id, :title, :counterparty_name, :counterparty_email, :start_date, :end_date, :notice_days, :payment_type, :rate_amount, :rate_currency_id, :retainer_amount, :status)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':project_id', $data['project_id'] ?? null);
            $statement->bindValue(':freelancer_id', $data['freelancer_id']);
            $statement->bindValue(':template_id', $data['template_id']);
            $statement->bindValue(':jurisdiction_country_id', $data['jurisdiction_country_id']);
            $statement->bindValue(':title', $data['title'] ?? null);
            $statement->bindValue(':counterparty_name', $data['counterparty_name'] ?? null);
            $statement->bindValue(':counterparty_email', $data['counterparty_email'] ?? null);
            $statement->bindValue(':start_date', $data['start_date'] ?? null);
            $statement->bindValue(':end_date', $data['end_date'] ?? null);
            $statement->bindValue(':notice_days', $data['notice_days'] ?? 0, \PDO::PARAM_INT);
            $statement->bindValue(':payment_type', $data['payment_type']);
            $statement->bindValue(':rate_amount', $data['rate_amount'] ?? null);
            $statement->bindValue(':rate_currency_id', $data['rate_currency_id']);
            $statement->bindValue(':retainer_amount', $data['retainer_amount'] ?? null);
            $statement->bindValue(':status', $data['status'] ?? 'draft');
            $statement->execute();

            return $this->findById($id, $data['company_id']);
        });
    }

    public function findById(string $id, string $companyId): ?Contract
    {
        return $this->guard(function () use ($id, $companyId): ?Contract {
            $query = 'SELECT * FROM contracts WHERE id = :id';
            $query = $this->withCompanyScope($query, $companyId, 'contracts');
            $query = $this->withSoftDeleteScope($query, 'contracts');

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $id);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            $legalApprovedAt = null;
            if (! empty($row['legal_approved_at'])) {
                $legalApprovedAt = new DateTimeImmutable((string) $row['legal_approved_at']);
            }

            $lastNotified = null;
            if (! empty($row['last_expiration_notified_at'])) {
                $lastNotified = new DateTimeImmutable((string) $row['last_expiration_notified_at']);
            }

            $deletedAt = null;
            if (! empty($row['deleted_at'])) {
                $deletedAt = new DateTimeImmutable((string) $row['deleted_at']);
            }

            return new Contract(
                id: (string) $row['id'],
                companyId: (string) $row['company_id'],
                freelancerId: (string) $row['freelancer_id'],
                templateId: (string) $row['template_id'],
                jurisdictionCountryId: (int) $row['jurisdiction_country_id'],
                paymentType: (string) $row['payment_type'],
                rateCurrencyId: (int) $row['rate_currency_id'],
                projectId: $row['project_id'] !== null ? (string) $row['project_id'] : null,
                title: $row['title'] !== null ? (string) $row['title'] : null,
                counterpartyName: $row['counterparty_name'] !== null ? (string) $row['counterparty_name'] : null,
                counterpartyEmail: $row['counterparty_email'] !== null ? (string) $row['counterparty_email'] : null,
                startDate: $row['start_date'] !== null ? (string) $row['start_date'] : null,
                endDate: $row['end_date'] !== null ? (string) $row['end_date'] : null,
                noticeDays: (int) $row['notice_days'],
                rateAmount: $row['rate_amount'] !== null ? (float) $row['rate_amount'] : null,
                retainerAmount: $row['retainer_amount'] !== null ? (float) $row['retainer_amount'] : null,
                currentVersionId: $row['current_version_id'] !== null ? (string) $row['current_version_id'] : null,
                status: (string) $row['status'],
                legalApprovedByCompanyUserId: $row['legal_approved_by_company_user_id'] !== null ? (string) $row['legal_approved_by_company_user_id'] : null,
                legalApprovedAt: $legalApprovedAt,
                lastExpirationNotifiedAt: $lastNotified,
                deletedAt: $deletedAt,
            );
        });
    }

    public function update(string $id, string $companyId, array $data): Contract
    {
        return $this->guard(function () use ($id, $companyId, $data): Contract {
            $fields = [];
            $bindings = [':id' => $id, ':company_id' => $companyId];

            foreach ($data as $column => $value) {
                $fields[] = sprintf('%s = :%s', $column, $column);
                $bindings[':' . $column] = $value;
            }

            if ($fields !== []) {
                $query = sprintf('UPDATE contracts SET %s WHERE id = :id AND company_id = :company_id', implode(', ', $fields));
                $statement = $this->connection->prepare($query);

                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value);
                }

                $statement->execute();
            }

            return $this->findById($id, $companyId);
        });
    }

    public function findExpiringWithin(string $companyId, int $days): array
    {
        return $this->guard(function () use ($companyId, $days): array {
            $query = 'SELECT * FROM contracts';
            $query = $this->withCompanyScope($query, $companyId, 'contracts');
            $query = $this->withSoftDeleteScope($query, 'contracts');
            $query .= ' AND end_date IS NOT NULL AND end_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)';

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':days', $days, \PDO::PARAM_INT);
            $statement->execute();

            $rows = $statement->fetchAll();
            $contracts = [];

            foreach ($rows as $row) {
                $contracts[] = new Contract(
                    id: (string) $row['id'],
                    companyId: (string) $row['company_id'],
                    freelancerId: (string) $row['freelancer_id'],
                    templateId: (string) $row['template_id'],
                    jurisdictionCountryId: (int) $row['jurisdiction_country_id'],
                    paymentType: (string) $row['payment_type'],
                    rateCurrencyId: (int) $row['rate_currency_id'],
                    projectId: $row['project_id'] !== null ? (string) $row['project_id'] : null,
                    title: $row['title'] !== null ? (string) $row['title'] : null,
                    counterpartyName: $row['counterparty_name'] !== null ? (string) $row['counterparty_name'] : null,
                    counterpartyEmail: $row['counterparty_email'] !== null ? (string) $row['counterparty_email'] : null,
                    startDate: $row['start_date'] !== null ? (string) $row['start_date'] : null,
                    endDate: $row['end_date'] !== null ? (string) $row['end_date'] : null,
                    noticeDays: (int) $row['notice_days'],
                    rateAmount: $row['rate_amount'] !== null ? (float) $row['rate_amount'] : null,
                    retainerAmount: $row['retainer_amount'] !== null ? (float) $row['retainer_amount'] : null,
                    currentVersionId: $row['current_version_id'] !== null ? (string) $row['current_version_id'] : null,
                    status: (string) $row['status'],
                    legalApprovedByCompanyUserId: $row['legal_approved_by_company_user_id'] !== null ? (string) $row['legal_approved_by_company_user_id'] : null,
                    legalApprovedAt: null,
                    lastExpirationNotifiedAt: null,
                    deletedAt: null,
                );
            }

            return $contracts;
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
