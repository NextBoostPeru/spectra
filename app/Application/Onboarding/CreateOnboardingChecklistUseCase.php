<?php

declare(strict_types=1);

namespace App\Application\Onboarding;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\OnboardingChecklistRepositoryInterface;

class CreateOnboardingChecklistUseCase implements UseCase
{
    public function __construct(private readonly OnboardingChecklistRepositoryInterface $checklists)
    {
    }

    public function __invoke(mixed $input): array
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $name = (string) ($input['name'] ?? '');
        if ($companyId === '' || $name === '') {
            throw new ApplicationException('El checklist requiere company_id y nombre.');
        }

        $checklist = $this->checklists->create($companyId, [
            'name' => $name,
            'description' => $input['description'] ?? null,
            'category' => $input['category'] ?? null,
        ]);

        return $checklist->toArray();
    }
}
