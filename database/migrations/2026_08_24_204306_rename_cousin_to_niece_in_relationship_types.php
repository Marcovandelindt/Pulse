<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('relationship_types')->where('name', 'Cousin')->update(['name' => 'Niece']);
    }

    public function down(): void
    {
        DB::table('relationship_types')->where('name', 'Niece')->update(['name' => 'Cousin']);
    }
};
