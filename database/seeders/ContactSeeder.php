<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RelationshipType;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Family',   'sort_order' => 1],
            ['name' => 'Friend',   'sort_order' => 2],
            ['name' => 'Partner',  'sort_order' => 3],
            ['name' => 'Colleague','sort_order' => 4],
            ['name' => 'Other',    'sort_order' => 5],
        ];

        foreach ($types as $type) {
            RelationshipType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
