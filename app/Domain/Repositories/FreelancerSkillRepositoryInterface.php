<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\FreelancerSkill;

interface FreelancerSkillRepositoryInterface
{
    /**
     * @param list<array{skill:string,level:int}> $skills
     * @return list<FreelancerSkill>
     */
    public function syncSkills(string $freelancerId, array $skills): array;

    /**
     * @return list<FreelancerSkill>
     */
    public function listByFreelancer(string $freelancerId): array;
}
