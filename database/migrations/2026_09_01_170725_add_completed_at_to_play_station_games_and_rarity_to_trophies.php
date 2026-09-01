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
            $table->timestamp('completed_at')->nullable()->after('trophies_last_synced_at');
        });

        Schema::table('play_station_trophies', function (Blueprint $table) {
            $table->unsignedTinyInteger('rarity')->nullable()->after('earned_at');
            $table->decimal('earned_rate', 5, 2)->nullable()->after('rarity');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });

        Schema::table('play_station_trophies', function (Blueprint $table) {
            $table->dropColumn(['rarity', 'earned_rate']);
        });
    }
};
