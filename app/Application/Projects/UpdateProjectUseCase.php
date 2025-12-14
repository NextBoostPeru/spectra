<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\ProjectRepositoryInterface;

class UpdateProjectUseCase implements UseCase
{
    public function __construct(private readonly ProjectRepositoryInterface $projects)
    {
    }

    /**
     * @param array{project_id:string,company_id:string,name?:string|null,description?:string|null,country_id?:int|null,currency_id?:int|null,status?:string|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos para actualizar proyecto.');
        }

        foreach (['project_id', 'company_id'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new ApplicationException(sprintf('Falta %s', $required));
            }
        }

        return $this->projects->update((string) $input['project_id'], (string) $input['company_id'], [
            'name' => $input['name'] ?? null,
            'description' => $input['description'] ?? null,
            'country_id' => $input['country_id'] ?? null,
            'currency_id' => $input['currency_id'] ?? null,
            'status' => $input['status'] ?? null,
        ])->toArray();
    }
}
