<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TvEpisode;
use App\Models\TvSeason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TvEpisode>
 */
final class TvEpisodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = $this->faker->numberBetween(1, 24);

        return [
            'tv_season_id' => TvSeason::factory(),
            'tmdb_id' => $this->faker->unique()->numberBetween(1, 999999),
            'name' => 'Episode '.$number,
            'overview' => null,
            'episode_number' => $number,
            'air_date' => $this->faker->dateTimeBetween('-4 years', '-1 year')->format('Y-m-d'),
            'runtime' => 45,
            'vote_average' => null,
        ];
    }
}
