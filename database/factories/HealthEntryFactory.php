<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HealthEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthEntry>
 */
final class HealthEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('-90 days', 'today')->format('Y-m-d'),
            'steps' => fake()->optional(0.9)->numberBetween(2000, 18000),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function goalMet(): static
    {
        return $this->state(['steps' => fake()->numberBetween(10000, 20000)]);
    }

    public function noSteps(): static
    {
        return $this->state(['steps' => null]);
    }
}
