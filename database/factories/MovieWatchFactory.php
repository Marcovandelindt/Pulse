<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Movie;
use App\Models\MovieWatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovieWatch>
 */
final class MovieWatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'watched_at' => now()->subDays($this->faker->numberBetween(0, 365)),
            'year_only' => false,
            'notes' => null,
            'rating' => $this->faker->numberBetween(1, 10),
        ];
    }
}
