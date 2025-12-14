<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\FreelancerProfile;

interface FreelancerProfileRepositoryInterface
{
    public function upsert(string $freelancerId, array $data): FreelancerProfile;

    public function findByFreelancerId(string $freelancerId): ?FreelancerProfile;
}
