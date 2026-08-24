<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the generic Family type (contacts become uncategorised, FK is nullOnDelete)
        DB::table('relationship_types')->where('name', 'Family')->delete();

        // Shift existing non-family types to make room
        DB::table('relationship_types')->increment('sort_order', 8);

        $family = [
            ['name' => 'Vader',  'sort_order' => 1],
            ['name' => 'Moeder', 'sort_order' => 2],
            ['name' => 'Broer',  'sort_order' => 3],
            ['name' => 'Nicht',  'sort_order' => 4],
            ['name' => 'Tante',  'sort_order' => 5],
            ['name' => 'Oom',    'sort_order' => 6],
            ['name' => 'Opa',    'sort_order' => 7],
            ['name' => 'Oma',    'sort_order' => 8],
        ];

        foreach ($family as $type) {
            DB::table('relationship_types')->insertOrIgnore([
                ...$type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('relationship_types')
            ->whereIn('name', ['Vader', 'Moeder', 'Broer', 'Nicht', 'Tante', 'Oom', 'Opa', 'Oma'])
            ->delete();

        DB::table('relationship_types')->decrement('sort_order', 8);

        DB::table('relationship_types')->insertOrIgnore([
            'name'       => 'Family',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
