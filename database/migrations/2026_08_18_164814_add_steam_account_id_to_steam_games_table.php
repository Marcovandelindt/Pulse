<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('steam_games')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('steam_games', function (Blueprint $table) {
            $table->foreignId('steam_account_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('steam_games', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\SteamAccount::class);
            $table->dropColumn('steam_account_id');
        });
    }
};
