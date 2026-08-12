<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EpisodeWatch;
use App\Models\TvEpisode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EpisodeWatch>
 */
final class EpisodeWatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tv_episode_id' => TvEpisode::factory(),
            'watched_at' => now()->subDays($this->faker->numberBetween(0, 365)),
            'year_only' => false,
            'notes' => null,
            'rating' => null,
        ];
    }
}
