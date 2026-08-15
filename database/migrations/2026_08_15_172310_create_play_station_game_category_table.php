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
        Schema::create('play_station_game_category', function (Blueprint $table) {
            $table->foreignId('play_station_game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('play_station_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['play_station_game_id', 'play_station_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_station_game_category');
    }
};
