<?php

declare(strict_types=1);

namespace App\Application\Deliverables;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\NpsResponseRepositoryInterface;

class RecordNpsResponseUseCase implements UseCase
{
    public function __construct(private readonly NpsResponseRepositoryInterface $responses)
    {
    }

    public function __invoke(mixed $input): array
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $projectId = (string) ($input['project_id'] ?? '');
        $respondent = (string) ($input['respondent_company_user_id'] ?? '');
        $score = (int) ($input['score'] ?? -1);

        if ($companyId === '' || $projectId === '' || $respondent === '' || $score < 0 || $score > 10) {
            throw new ApplicationException('Respuesta NPS inválida.');
        }

        $response = $this->responses->create([
            'company_id' => $companyId,
            'project_id' => $projectId,
            'respondent_company_user_id' => $respondent,
            'score' => $score,
            'comment' => $input['comment'] ?? null,
        ]);

        return $response->toArray();
    }
}
