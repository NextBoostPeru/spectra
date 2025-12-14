<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Domain\Repositories\ProjectMemberRepositoryInterface;

class CreateProjectUseCase implements UseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $projectMembers,
    ) {
    }

    /**
     * @param array{company_id:string,name:string,country_id:int,currency_id:int,description?:string|null,created_by_company_user_id?:string|null,members?:list<array{company_user_id:string,role?:string}>} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos para proyecto.');
        }

        foreach (['company_id', 'name', 'country_id', 'currency_id'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new ApplicationException(sprintf('Falta %s', $required));
            }
        }

        $project = $this->projects->create((string) $input['company_id'], [
            'name' => (string) $input['name'],
            'description' => $input['description'] ?? null,
            'country_id' => (int) $input['country_id'],
            'currency_id' => (int) $input['currency_id'],
            'status' => 'active',
            'created_by_company_user_id' => $input['created_by_company_user_id'] ?? null,
        ]);

        $members = [];
        foreach ($input['members'] ?? [] as $member) {
            if (! is_array($member) || empty($member['company_user_id'])) {
                continue;
            }

            $role = $member['role'] ?? 'viewer';
            $members[] = $this->projectMembers->addMember($project->id(), (string) $member['company_user_id'], (string) $role)->toArray();
        }

        return [
            'project' => $project->toArray(),
            'members' => $members,
        ];
    }
}
