<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\ProjectMemberRepositoryInterface;
use App\Domain\Repositories\ProjectRepositoryInterface;

class AddProjectMemberUseCase implements UseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $projectMembers,
    ) {
    }

    /**
     * @param array{project_id:string,company_id:string,company_user_id:string,role?:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos para miembro de proyecto.');
        }

        foreach (['project_id', 'company_id', 'company_user_id'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new ApplicationException(sprintf('Falta %s', $required));
            }
        }

        $project = $this->projects->findById((string) $input['project_id'], (string) $input['company_id']);
        if ($project === null) {
            throw new ApplicationException('Proyecto no encontrado.');
        }

        $member = $this->projectMembers->addMember(
            $project->id(),
            (string) $input['company_user_id'],
            (string) ($input['role'] ?? 'viewer'),
        );

        return $member->toArray();
    }
}
