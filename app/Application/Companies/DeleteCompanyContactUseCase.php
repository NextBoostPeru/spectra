<?php

declare(strict_types=1);

namespace App\Application\Companies;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyContactRepositoryInterface;
use App\Domain\Repositories\CompanyRepositoryInterface;

class DeleteCompanyContactUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly CompanyContactRepositoryInterface $contacts,
    ) {
    }

    /**
     * @param array{company_id:string,contact_id:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id']) || empty($input['contact_id'])) {
            throw new ApplicationException('company_id y contact_id son requeridos');
        }

        $company = $this->companies->findById((string) $input['company_id']);

        if ($company === null) {
            throw new ApplicationException('Compañía no encontrada.');
        }

        $this->contacts->delete($company->id(), (string) $input['contact_id']);

        return [
            'message' => 'Contacto eliminado',
            'contact_id' => (string) $input['contact_id'],
        ];
    }
}
