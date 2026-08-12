<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TvSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TvSeries>
 */
final class TvSeriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tmdb_id' => $this->faker->unique()->numberBetween(1, 999999),
            'name' => $this->faker->words(2, true),
            'name_en' => null,
            'original_name' => $this->faker->words(2, true),
            'overview' => $this->faker->paragraph(),
            'poster_path' => '/poster_'.$this->faker->uuid().'.jpg',
            'backdrop_path' => null,
            'first_air_date' => $this->faker->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'last_air_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'vote_average' => $this->faker->randomFloat(1, 4, 9),
            'genres' => ['Drama'],
            'status' => 'Ended',
            'original_language' => 'en',
            'number_of_seasons' => 1,
            'number_of_episodes' => 10,
            'episodes_watched' => 0,
            'completion_percentage' => 0.00,
            'last_watched_at' => null,
            'first_watched_at' => null,
        ];
    }
}
