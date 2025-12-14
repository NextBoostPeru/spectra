<?php

declare(strict_types=1);

namespace App\Application\Assignments;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\AssignmentRepositoryInterface;
use App\Domain\Repositories\ProjectRepositoryInterface;

class ListAssignmentsByProjectUseCase implements UseCase
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignments,
        private readonly ProjectRepositoryInterface $projects,
    ) {
    }

    /**
     * @param array{company_id:string,project_id:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos para listar asignaciones.');
        }

        foreach (['company_id', 'project_id'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new ApplicationException(sprintf('Falta %s', $required));
            }
        }

        $project = $this->projects->findById((string) $input['project_id'], (string) $input['company_id']);
        if ($project === null) {
            throw new ApplicationException('Proyecto no encontrado.');
        }

        $assignments = $this->assignments->listByProject($project->id(), $project->companyId());

        return array_map(static fn ($assignment) => $assignment->toArray(), $assignments);
    }
}
