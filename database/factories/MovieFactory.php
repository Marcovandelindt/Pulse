<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
final class MovieFactory extends Factory
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
            'title' => $this->faker->words(3, true),
            'original_title' => $this->faker->words(3, true),
            'overview' => $this->faker->paragraph(),
            'poster_path' => '/poster_'.$this->faker->uuid().'.jpg',
            'backdrop_path' => null,
            'release_date' => $this->faker->dateTimeBetween('-20 years', 'now')->format('Y-m-d'),
            'runtime' => $this->faker->numberBetween(70, 180),
            'vote_average' => $this->faker->randomFloat(1, 4, 9),
            'genres' => ['Action', 'Drama'],
            'original_language' => 'en',
            'watch_count' => 0,
            'last_watched_at' => null,
            'first_watched_at' => null,
        ];
    }
}
