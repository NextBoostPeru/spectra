<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\FreelancerSkill;
use App\Domain\Repositories\FreelancerSkillRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;
use PDO;

class FreelancerSkillRepository extends PdoRepository implements FreelancerSkillRepositoryInterface
{
    public function syncSkills(string $freelancerId, array $skills): array
    {
        return $this->guard(function () use ($freelancerId, $skills): array {
            $this->connection->beginTransaction();

            try {
                $delete = $this->connection->prepare('DELETE FROM freelancer_skills WHERE freelancer_id = :id');
                $delete->bindValue(':id', $freelancerId);
                $delete->execute();

                $insert = $this->connection->prepare('INSERT INTO freelancer_skills (freelancer_id, skill, level) VALUES (:freelancer_id, :skill, :level)');

                foreach ($skills as $skill) {
                    $insert->bindValue(':freelancer_id', $freelancerId);
                    $insert->bindValue(':skill', $skill['skill']);
                    $insert->bindValue(':level', $skill['level'], PDO::PARAM_INT);
                    $insert->execute();
                }

                $this->connection->commit();
            } catch (\Throwable $exception) {
                $this->connection->rollBack();
                throw $exception;
            }

            return $this->listByFreelancer($freelancerId);
        });
    }

    public function listByFreelancer(string $freelancerId): array
    {
        return $this->guard(function () use ($freelancerId): array {
            $statement = $this->connection->prepare('SELECT freelancer_id, skill, level FROM freelancer_skills WHERE freelancer_id = :id ORDER BY skill');
            $statement->bindValue(':id', $freelancerId);
            $statement->execute();

            $rows = $statement->fetchAll();

            return array_map(
                static fn (array $row): FreelancerSkill => new FreelancerSkill(
                    freelancerId: (string) $row['freelancer_id'],
                    skill: (string) $row['skill'],
                    level: (int) $row['level'],
                ),
                $rows,
            );
        });
    }
}
