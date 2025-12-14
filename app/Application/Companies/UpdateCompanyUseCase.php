<?php

declare(strict_types=1);

namespace App\Application\Companies;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyRepositoryInterface;
use App\Domain\Repositories\CompanySettingsRepositoryInterface;
use App\Domain\Repositories\CompanyContactRepositoryInterface;

class UpdateCompanyUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly CompanySettingsRepositoryInterface $settings,
        private readonly CompanyContactRepositoryInterface $contacts,
    ) {
    }

    /**
     * @param array{company_id:string,legal_name?:string,trade_name?:string|null,country_id?:int,default_currency_id?:int,time_zone?:string,settings?:array<string,mixed>,contacts?:list<array<string,mixed>>} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id'])) {
            throw new ApplicationException('Se requiere company_id.');
        }

        $companyId = (string) $input['company_id'];
        $company = $this->companies->findById($companyId);

        if ($company === null) {
            throw new ApplicationException('Compañía no encontrada.');
        }

        $payload = [];

        foreach (['legal_name', 'trade_name', 'country_id', 'default_currency_id', 'timezone', 'status'] as $field) {
            if (array_key_exists($field, $input)) {
                $payload[$field] = $input[$field];
            }
        }

        $updatedCompany = $payload === [] ? $company : $this->companies->update($companyId, $payload);
        $updatedSettings = $this->settings->upsert($companyId, (array) ($input['settings'] ?? []));

        $contacts = [];

        foreach ($input['contacts'] ?? [] as $contact) {
            if (! is_array($contact) || empty($contact['id'])) {
                continue;
            }

            $contacts[] = $this->contacts->update($companyId, (string) $contact['id'], $contact)->toArray();
        }

        return [
            'company' => [
                'id' => $updatedCompany->id(),
                'legal_name' => $updatedCompany->legalName(),
                'trade_name' => $updatedCompany->tradeName(),
                'country_id' => $updatedCompany->countryId(),
                'default_currency_id' => $updatedCompany->defaultCurrencyId(),
                'timezone' => $updatedCompany->timezone(),
            ],
            'settings' => $updatedSettings->toArray(),
            'contacts' => $contacts === [] ? array_map(fn ($contact) => $contact->toArray(), $this->contacts->listForCompany($companyId)) : $contacts,
        ];
    }
}
