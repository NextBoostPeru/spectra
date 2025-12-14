<?php

declare(strict_types=1);

namespace App\Application\Companies;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyContactRepositoryInterface;
use App\Domain\Repositories\CompanyRepositoryInterface;

class AddCompanyContactUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly CompanyContactRepositoryInterface $contacts,
    ) {
    }

    /**
     * @param array{company_id:string,name:string,email:string,phone?:string|null,type?:string,is_primary?:bool} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id'])) {
            throw new ApplicationException('company_id requerido');
        }

        if (empty($input['name']) || empty($input['email'])) {
            throw new ApplicationException('Nombre y correo son obligatorios.');
        }

        $company = $this->companies->findById((string) $input['company_id']);

        if ($company === null) {
            throw new ApplicationException('Compañía no encontrada.');
        }

        $contact = $this->contacts->create($company->id(), $input);

        return $contact->toArray();
    }
}
