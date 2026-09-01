<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->dropUnique(['name', 'platform']);
            // Manual entries (exclude_from_sync=1) may share a name+platform with a synced entry.
            $table->unique(['name', 'platform', 'exclude_from_sync']);
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->dropUnique(['name', 'platform', 'exclude_from_sync']);
            $table->unique(['name', 'platform']);
        });
    }
};
