<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HealthEntry;
use Illuminate\Database\Seeder;

final class HealthEntrySeeder extends Seeder
{
    public function run(): void
    {
        HealthEntry::factory()->count(60)->create();
    }
}
