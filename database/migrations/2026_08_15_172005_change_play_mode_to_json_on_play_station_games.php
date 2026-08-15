<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wrap existing single values in a JSON array before changing column type
        DB::table('play_station_games')
            ->whereNotNull('play_mode')
            ->get(['id', 'play_mode'])
            ->each(function ($game) {
                $value = $game->play_mode;
                // Already JSON? skip. Otherwise wrap in array.
                if (! str_starts_with($value, '[')) {
                    DB::table('play_station_games')
                        ->where('id', $game->id)
                        ->update(['play_mode' => json_encode([$value])]);
                }
            });

        Schema::table('play_station_games', function (Blueprint $table) {
            $table->json('play_mode')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->string('play_mode')->nullable()->change();
        });
    }
};
