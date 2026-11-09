<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'sqlite' => DB::statement("UPDATE plays SET played_at = datetime(played_at, '+2 hours')"),
            default => DB::statement('UPDATE plays SET played_at = DATE_ADD(played_at, INTERVAL 2 HOUR)'),
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'sqlite' => DB::statement("UPDATE plays SET played_at = datetime(played_at, '-2 hours')"),
            default => DB::statement('UPDATE plays SET played_at = DATE_SUB(played_at, INTERVAL 2 HOUR)'),
        };
    }
};
