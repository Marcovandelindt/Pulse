<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE plays SET played_at = DATE_ADD(played_at, INTERVAL 2 HOUR)');
    }

    public function down(): void
    {
        DB::statement('UPDATE plays SET played_at = DATE_SUB(played_at, INTERVAL 2 HOUR)');
    }
};
