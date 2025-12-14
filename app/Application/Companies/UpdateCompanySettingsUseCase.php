<?php

declare(strict_types=1);

namespace App\Application\Companies;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyRepositoryInterface;
use App\Domain\Repositories\CompanySettingsRepositoryInterface;

class UpdateCompanySettingsUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly CompanySettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * @param array{company_id:string,settings:array<string,mixed>} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id']) || ! is_array($input['settings'] ?? null)) {
            throw new ApplicationException('Datos de settings inválidos');
        }

        $company = $this->companies->findById((string) $input['company_id']);

        if ($company === null) {
            throw new ApplicationException('Compañía no encontrada.');
        }

        $settings = $this->settings->upsert($company->id(), $input['settings']);

        return $settings->toArray();
    }
}
