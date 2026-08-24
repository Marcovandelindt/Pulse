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
            ['name' => 'Father',      'sort_order' => 1],
            ['name' => 'Mother',      'sort_order' => 2],
            ['name' => 'Brother',     'sort_order' => 3],
            ['name' => 'Cousin',      'sort_order' => 4],
            ['name' => 'Aunt',        'sort_order' => 5],
            ['name' => 'Uncle',       'sort_order' => 6],
            ['name' => 'Grandfather', 'sort_order' => 7],
            ['name' => 'Grandmother', 'sort_order' => 8],
            ['name' => 'Friend',    'sort_order' => 9],
            ['name' => 'Partner',   'sort_order' => 10],
            ['name' => 'Colleague', 'sort_order' => 11],
            ['name' => 'Other',     'sort_order' => 12],
        ];

        foreach ($types as $type) {
            RelationshipType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
