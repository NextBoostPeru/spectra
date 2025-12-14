<?php

declare(strict_types=1);

namespace App\Application\Assignments;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\AssignmentRepositoryInterface;
use App\Domain\Repositories\FreelancerRepositoryInterface;
use App\Domain\Repositories\ProjectRepositoryInterface;

class CreateAssignmentUseCase implements UseCase
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignments,
        private readonly ProjectRepositoryInterface $projects,
        private readonly FreelancerRepositoryInterface $freelancers,
    ) {
    }

    /**
     * @param array{company_id:string,project_id:string,freelancer_id:string,role_title:string,start_date:string,end_date?:string|null,contract_id?:string|null,status?:string|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos para asignación.');
        }

        foreach (['company_id', 'project_id', 'freelancer_id', 'role_title', 'start_date'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new ApplicationException(sprintf('Falta %s', $required));
            }
        }

        $project = $this->projects->findById((string) $input['project_id'], (string) $input['company_id']);
        if ($project === null) {
            throw new ApplicationException('Proyecto no encontrado para la compañía.');
        }

        $freelancer = $this->freelancers->findById((string) $input['freelancer_id']);
        if ($freelancer === null) {
            throw new ApplicationException('Freelancer no encontrado.');
        }

        $assignment = $this->assignments->create([
            'company_id' => (string) $input['company_id'],
            'project_id' => $project->id(),
            'freelancer_id' => $freelancer->id(),
            'contract_id' => $input['contract_id'] ?? null,
            'role_title' => (string) $input['role_title'],
            'start_date' => (string) $input['start_date'],
            'end_date' => $input['end_date'] ?? null,
            'status' => $input['status'] ?? 'active',
        ]);

        return $assignment->toArray();
    }
}
