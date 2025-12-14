<?php

declare(strict_types=1);

namespace App\Application\Freelancers;

use App\Application\Contracts\UseCase;
use App\Application\Pagination\PaginationRequest;
use App\Application\Pagination\PaginationResult;
use App\Domain\Repositories\FreelancerRepositoryInterface;
use App\Domain\Repositories\FreelancerProfileRepositoryInterface;
use App\Domain\Repositories\FreelancerSkillRepositoryInterface;

class ListFreelancersUseCase implements UseCase
{
    public function __construct(
        private readonly FreelancerRepositoryInterface $freelancers,
        private readonly FreelancerProfileRepositoryInterface $profiles,
        private readonly FreelancerSkillRepositoryInterface $skills,
    ) {
    }

    /**
     * @param array{page?:int,page_size?:int} $input
     */
    public function __invoke(mixed $input): PaginationResult
    {
        $page = isset($input['page']) ? (int) $input['page'] : 1;
        $pageSize = isset($input['page_size']) ? (int) $input['page_size'] : 15;
        $pagination = new PaginationRequest($page, $pageSize);

        $items = $this->freelancers->paginate($pagination->page, $pagination->pageSize);
        $total = $this->freelancers->count();

        $data = [];
        foreach ($items as $row) {
            $profile = $this->profiles->findByFreelancerId((string) $row['id']);
            $skills = $this->skills->listByFreelancer((string) $row['id']);

            $data[] = [
                'id' => $row['id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'status' => $row['status'],
                'profile' => $profile?->toArray(),
                'skills' => array_map(static fn ($skill) => $skill->toArray(), $skills),
            ];
        }

        return new PaginationResult(
            items: $data,
            total: $total,
            page: $pagination->page,
            pageSize: $pagination->pageSize,
        );
    }
}
