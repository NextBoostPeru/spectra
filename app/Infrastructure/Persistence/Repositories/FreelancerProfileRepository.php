<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\FreelancerProfile;
use App\Domain\Repositories\FreelancerProfileRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class FreelancerProfileRepository extends PdoRepository implements FreelancerProfileRepositoryInterface
{
    public function upsert(string $freelancerId, array $data): FreelancerProfile
    {
        return $this->guard(function () use ($freelancerId, $data): FreelancerProfile {
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO freelancer_profiles (freelancer_id, country_id, primary_currency_id, headline, bio, hourly_rate_min, hourly_rate_max, seniority_level, availability_status)
VALUES (:freelancer_id, :country_id, :primary_currency_id, :headline, :bio, :hourly_rate_min, :hourly_rate_max, :seniority_level, :availability_status)
ON DUPLICATE KEY UPDATE
    country_id = VALUES(country_id),
    primary_currency_id = VALUES(primary_currency_id),
    headline = VALUES(headline),
    bio = VALUES(bio),
    hourly_rate_min = VALUES(hourly_rate_min),
    hourly_rate_max = VALUES(hourly_rate_max),
    seniority_level = VALUES(seniority_level),
    availability_status = VALUES(availability_status)
SQL);

            $statement->bindValue(':freelancer_id', $freelancerId);
            $statement->bindValue(':country_id', $data['country_id']);
            $statement->bindValue(':primary_currency_id', $data['primary_currency_id']);
            $statement->bindValue(':headline', $data['headline'] ?? null);
            $statement->bindValue(':bio', $data['bio'] ?? null);
            $statement->bindValue(':hourly_rate_min', $data['hourly_rate_min'] ?? null);
            $statement->bindValue(':hourly_rate_max', $data['hourly_rate_max'] ?? null);
            $statement->bindValue(':seniority_level', $data['seniority_level'] ?? null);
            $statement->bindValue(':availability_status', $data['availability_status'] ?? 'available');
            $statement->execute();

            return $this->findByFreelancerId($freelancerId);
        });
    }

    public function findByFreelancerId(string $freelancerId): ?FreelancerProfile
    {
        return $this->guard(function () use ($freelancerId): ?FreelancerProfile {
            $statement = $this->connection->prepare('SELECT freelancer_id, country_id, primary_currency_id, headline, bio, hourly_rate_min, hourly_rate_max, seniority_level, availability_status FROM freelancer_profiles WHERE freelancer_id = :id');
            $statement->bindValue(':id', $freelancerId);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            return new FreelancerProfile(
                freelancerId: (string) $row['freelancer_id'],
                countryId: (int) $row['country_id'],
                primaryCurrencyId: (int) $row['primary_currency_id'],
                headline: $row['headline'] !== null ? (string) $row['headline'] : null,
                bio: $row['bio'] !== null ? (string) $row['bio'] : null,
                hourlyRateMin: $row['hourly_rate_min'] !== null ? (float) $row['hourly_rate_min'] : null,
                hourlyRateMax: $row['hourly_rate_max'] !== null ? (float) $row['hourly_rate_max'] : null,
                seniorityLevel: $row['seniority_level'] !== null ? (string) $row['seniority_level'] : null,
                availabilityStatus: (string) $row['availability_status'],
            );
        });
    }
}
