<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_station_game_images', function (Blueprint $table) {
            $table->string('disk')->default('public')->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_game_images', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};
