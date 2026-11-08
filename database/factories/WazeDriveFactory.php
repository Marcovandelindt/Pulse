<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WazeDrive;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WazeDrive>
 */
final class WazeDriveFactory extends Factory
{
    public function definition(): array
    {
        $originLat = $this->faker->latitude(52.0, 52.5);
        $originLon = $this->faker->longitude(4.7, 5.0);
        $destLat = $this->faker->latitude(52.0, 52.5);
        $destLon = $this->faker->longitude(4.7, 5.0);

        return [
            'driven_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'origin_name' => $this->faker->streetAddress().', Netherlands',
            'origin_lat' => $originLat,
            'origin_lon' => $originLon,
            'destination_name' => $this->faker->streetAddress().', Netherlands',
            'destination_lat' => $destLat,
            'destination_lon' => $destLon,
            'distance_km' => $this->faker->randomFloat(2, 1, 50),
        ];
    }
}
