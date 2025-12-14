<?php

declare(strict_types=1);

namespace App\Application\Freelancers;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\FreelancerRepositoryInterface;
use App\Domain\Repositories\FreelancerProfileRepositoryInterface;
use App\Domain\Repositories\FreelancerSkillRepositoryInterface;

class CreateFreelancerUseCase implements UseCase
{
    public function __construct(
        private readonly FreelancerRepositoryInterface $freelancers,
        private readonly FreelancerProfileRepositoryInterface $profiles,
        private readonly FreelancerSkillRepositoryInterface $skills,
    ) {
    }

    /**
     * @param array{full_name:string,email:string,country_id:int,primary_currency_id:int,headline?:string|null,bio?:string|null,hourly_rate_min?:float|null,hourly_rate_max?:float|null,seniority_level?:string|null,availability_status?:string|null,skills?:list<array{skill:string,level:int}>} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos.');
        }

        foreach (['full_name', 'email', 'country_id', 'primary_currency_id'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new ApplicationException(sprintf('Falta %s', $required));
            }
        }

        $freelancer = $this->freelancers->create([
            'full_name' => (string) $input['full_name'],
            'email' => (string) $input['email'],
            'status' => 'pending',
        ]);

        $profile = $this->profiles->upsert($freelancer->id(), [
            'country_id' => (int) $input['country_id'],
            'primary_currency_id' => (int) $input['primary_currency_id'],
            'headline' => $input['headline'] ?? null,
            'bio' => $input['bio'] ?? null,
            'hourly_rate_min' => $input['hourly_rate_min'] ?? null,
            'hourly_rate_max' => $input['hourly_rate_max'] ?? null,
            'seniority_level' => $input['seniority_level'] ?? null,
            'availability_status' => $input['availability_status'] ?? 'available',
        ]);

        $skills = $this->skills->syncSkills($freelancer->id(), array_map(
            static fn (array $skill): array => [
                'skill' => (string) $skill['skill'],
                'level' => (int) $skill['level'],
            ],
            $input['skills'] ?? [],
        ));

        return [
            'freelancer' => $freelancer->toArray(),
            'profile' => $profile->toArray(),
            'skills' => array_map(static fn ($skill) => $skill->toArray(), $skills),
        ];
    }
}
