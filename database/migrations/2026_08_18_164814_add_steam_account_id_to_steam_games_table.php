<?php

declare(strict_types=1);

use App\Models\SteamAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::getDriverName()) {
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            default => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
        };

        DB::table('steam_games')->truncate();

        match (DB::getDriverName()) {
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
        };

        Schema::table('steam_games', function (Blueprint $table): void {
            $table->foreignId('steam_account_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('steam_games', function (Blueprint $table): void {
            $table->dropForeignIdFor(SteamAccount::class);
            $table->dropColumn('steam_account_id');
        });
    }
};
