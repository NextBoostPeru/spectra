<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class FreelancerSkill
{
    public function __construct(
        private readonly string $freelancerId,
        private readonly string $skill,
        private readonly int $level,
    ) {
    }

    public function toArray(): array
    {
        return [
            'freelancer_id' => $this->freelancerId,
            'skill' => $this->skill,
            'level' => $this->level,
        ];
    }
}
