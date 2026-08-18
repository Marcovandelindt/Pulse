<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('steam_games', function (Blueprint $table) {
            $table->dropUnique('steam_games_steam_appid_unique');
            $table->unique(['steam_account_id', 'steam_appid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('steam_games', function (Blueprint $table) {
            $table->dropUnique(['steam_account_id', 'steam_appid']);
            $table->unique('steam_appid');
        });
    }
};
