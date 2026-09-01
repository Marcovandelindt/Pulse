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
            $table->string('trophy_search_name')->nullable()->after('display_name');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->dropColumn('trophy_search_name');
        });
    }
};
