<?php

declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\NpsResponse;
use App\Domain\Repositories\NpsResponseRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class NpsResponseRepository extends PdoRepository implements NpsResponseRepositoryInterface
{
    public function create(array $data): NpsResponse
    {
        return $this->guard(function () use ($data): NpsResponse {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO nps_responses (id, company_id, project_id, respondent_company_user_id, score, comment)
VALUES (:id, :company_id, :project_id, :respondent_company_user_id, :score, :comment)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':project_id', $data['project_id']);
            $statement->bindValue(':respondent_company_user_id', $data['respondent_company_user_id']);
            $statement->bindValue(':score', $data['score']);
            $statement->bindValue(':comment', $data['comment'] ?? null);
            $statement->execute();

            return new NpsResponse(
                id: $id,
                companyId: $data['company_id'],
                projectId: $data['project_id'],
                respondentCompanyUserId: $data['respondent_company_user_id'],
                score: (int) $data['score'],
                comment: $data['comment'] ?? null,
                submittedAt: (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            );
        });
    }

    public function listByProject(string $companyId, string $projectId): array
    {
        return $this->guard(function () use ($companyId, $projectId): array {
            $query = 'SELECT id, company_id, project_id, respondent_company_user_id, score, comment, created_at FROM nps_responses WHERE project_id = :project_id';
            $query = $this->withCompanyScope($query, $companyId, 'nps_responses');
            $query .= ' ORDER BY created_at DESC';
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':project_id', $projectId);
            $statement->execute();

            $responses = [];
            foreach ($statement->fetchAll() as $row) {
                $responses[] = new NpsResponse(
                    id: (string) $row['id'],
                    companyId: (string) $row['company_id'],
                    projectId: (string) $row['project_id'],
                    respondentCompanyUserId: (string) $row['respondent_company_user_id'],
                    score: (int) $row['score'],
                    comment: $row['comment'] !== null ? (string) $row['comment'] : null,
                    submittedAt: (string) $row['created_at'],
                );
            }

            return $responses;
        });
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }
}
