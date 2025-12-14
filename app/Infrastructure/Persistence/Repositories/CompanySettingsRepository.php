<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\CompanySettings;
use App\Domain\Repositories\CompanySettingsRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class CompanySettingsRepository extends PdoRepository implements CompanySettingsRepositoryInterface
{
    public function getByCompanyId(string $companyId): CompanySettings
    {
        return $this->guard(function () use ($companyId) {
            $statement = $this->connection->prepare('SELECT company_id, tax_id, tax_regime, billing_address, invoice_series, invoice_number_next, default_language FROM company_settings WHERE company_id = :company_id');
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return new CompanySettings($companyId, null, null, null, null, 1, 'es');
            }

            return $this->hydrate($row);
        });
    }

    public function upsert(string $companyId, array $settings): CompanySettings
    {
        return $this->guard(function () use ($companyId, $settings) {
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO company_settings (company_id, tax_id, tax_regime, billing_address, invoice_series, invoice_number_next, default_language)
VALUES (:company_id, :tax_id, :tax_regime, :billing_address, :invoice_series, :invoice_number_next, :default_language)
ON DUPLICATE KEY UPDATE
 tax_id = VALUES(tax_id),
 tax_regime = VALUES(tax_regime),
 billing_address = VALUES(billing_address),
 invoice_series = VALUES(invoice_series),
 invoice_number_next = VALUES(invoice_number_next),
 default_language = VALUES(default_language)
SQL);

            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':tax_id', $settings['tax_id'] ?? null);
            $statement->bindValue(':tax_regime', $settings['tax_regime'] ?? null);
            $statement->bindValue(':billing_address', $settings['billing_address'] ?? null);
            $statement->bindValue(':invoice_series', $settings['invoice_series'] ?? null);
            $statement->bindValue(':invoice_number_next', $settings['invoice_number_next'] ?? 1, \PDO::PARAM_INT);
            $statement->bindValue(':default_language', $settings['default_language'] ?? 'es');
            $statement->execute();

            return $this->getByCompanyId($companyId);
        });
    }

    private function hydrate(array $row): CompanySettings
    {
        return new CompanySettings(
            companyId: (string) $row['company_id'],
            taxId: $row['tax_id'] !== null ? (string) $row['tax_id'] : null,
            taxRegime: $row['tax_regime'] !== null ? (string) $row['tax_regime'] : null,
            billingAddress: $row['billing_address'] !== null ? (string) $row['billing_address'] : null,
            invoiceSeries: $row['invoice_series'] !== null ? (string) $row['invoice_series'] : null,
            invoiceNumberNext: (int) $row['invoice_number_next'],
            defaultLanguage: (string) $row['default_language'],
        );
    }
}
