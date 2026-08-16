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
            $table->unsignedInteger('trophy_progress')->default(0)->after('trophies');
            $table->json('trophy_earned')->nullable()->after('trophy_progress');
            $table->json('trophy_defined')->nullable()->after('trophy_earned');
            $table->timestamp('trophies_last_synced_at')->nullable()->after('trophy_defined');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->dropColumn(['trophy_progress', 'trophy_earned', 'trophy_defined', 'trophies_last_synced_at']);
        });
    }
};
