<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TvSeason;
use App\Models\TvSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TvSeason>
 */
final class TvSeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tv_series_id' => TvSeries::factory(),
            'tmdb_id' => $this->faker->unique()->numberBetween(1, 999999),
            'name' => 'Season 1',
            'overview' => null,
            'poster_path' => null,
            'season_number' => 1,
            'air_date' => $this->faker->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'episode_count' => 10,
            'episodes_watched' => 0,
        ];
    }
}
