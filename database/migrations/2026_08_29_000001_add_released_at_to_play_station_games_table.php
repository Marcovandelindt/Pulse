<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_station_games', function (Blueprint $table): void {
            $table->date('released_at')->nullable()->after('last_played_at');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table): void {
            $table->dropColumn('released_at');
        });
    }
};
