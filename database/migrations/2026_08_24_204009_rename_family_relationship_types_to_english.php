<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            'Vader'  => 'Father',
            'Moeder' => 'Mother',
            'Broer'  => 'Brother',
            'Nicht'  => 'Cousin',
            'Tante'  => 'Aunt',
            'Oom'    => 'Uncle',
            'Opa'    => 'Grandfather',
            'Oma'    => 'Grandmother',
        ];

        foreach ($renames as $old => $new) {
            DB::table('relationship_types')->where('name', $old)->update(['name' => $new]);
        }
    }

    public function down(): void
    {
        $renames = [
            'Father'      => 'Vader',
            'Mother'      => 'Moeder',
            'Brother'     => 'Broer',
            'Cousin'      => 'Nicht',
            'Aunt'        => 'Tante',
            'Uncle'       => 'Oom',
            'Grandfather' => 'Opa',
            'Grandmother' => 'Oma',
        ];

        foreach ($renames as $old => $new) {
            DB::table('relationship_types')->where('name', $old)->update(['name' => $new]);
        }
    }
};
