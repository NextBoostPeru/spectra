<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class FreelancerProfile
{
    public function __construct(
        private readonly string $freelancerId,
        private readonly int $countryId,
        private readonly int $primaryCurrencyId,
        private readonly ?string $headline = null,
        private readonly ?string $bio = null,
        private readonly ?float $hourlyRateMin = null,
        private readonly ?float $hourlyRateMax = null,
        private readonly ?string $seniorityLevel = null,
        private readonly string $availabilityStatus = 'available',
    ) {
    }

    public function freelancerId(): string
    {
        return $this->freelancerId;
    }

    public function toArray(): array
    {
        return [
            'freelancer_id' => $this->freelancerId,
            'country_id' => $this->countryId,
            'primary_currency_id' => $this->primaryCurrencyId,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'hourly_rate_min' => $this->hourlyRateMin,
            'hourly_rate_max' => $this->hourlyRateMax,
            'seniority_level' => $this->seniorityLevel,
            'availability_status' => $this->availabilityStatus,
        ];
    }
}
