<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Exceptions\ApplicationException;
use App\Application\Freelancers\CreateFreelancerUseCase;
use App\Application\Freelancers\ListFreelancersUseCase;
use App\Application\Freelancers\UpdateFreelancerProfileUseCase;
use App\Interface\Http\Requests\RequestValidator;
use InvalidArgumentException;

class FreelancerController extends Controller
{
    public function __construct(
        private readonly ListFreelancersUseCase $listFreelancers,
        private readonly CreateFreelancerUseCase $createFreelancer,
        private readonly UpdateFreelancerProfileUseCase $updateProfile,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function index(array $request): string
    {
        try {
            $page = (int) ($request['page'] ?? 1);
            $pageSize = (int) ($request['page_size'] ?? 15);
            $result = ($this->listFreelancers)(['page' => $page, 'page_size' => $pageSize]);
            $payload = $result->toArray();

            return $this->ok($payload['data'], $payload['meta']);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function store(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'full_name' => static fn ($value): bool => is_string($value) && $value !== '',
                'email' => static fn ($value): bool => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                'country_id' => static fn ($value): bool => is_numeric($value),
                'primary_currency_id' => static fn ($value): bool => is_numeric($value),
                'skills' => static fn ($value): bool => $value === null || is_array($value),
            ]);

            $result = ($this->createFreelancer)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function updateProfile(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'freelancer_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'country_id' => static fn ($value): bool => is_numeric($value),
                'primary_currency_id' => static fn ($value): bool => is_numeric($value),
                'skills' => static fn ($value): bool => $value === null || is_array($value),
            ]);

            $result = ($this->updateProfile)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
