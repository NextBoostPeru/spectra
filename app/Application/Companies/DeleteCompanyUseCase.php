<?php

declare(strict_types=1);

namespace App\Application\Companies;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyRepositoryInterface;

class DeleteCompanyUseCase implements UseCase
{
    public function __construct(private readonly CompanyRepositoryInterface $companies)
    {
    }

    /**
     * @param array{company_id:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id'])) {
            throw new ApplicationException('Se requiere company_id');
        }

        $company = $this->companies->findById((string) $input['company_id']);

        if ($company === null) {
            throw new ApplicationException('Compañía no encontrada.');
        }

        $this->companies->softDelete($company->id());

        return [
            'message' => 'Compañía eliminada con soft delete',
            'id' => $company->id(),
        ];
    }
}
