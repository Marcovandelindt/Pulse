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
        Schema::create('nintendo_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nintendo_game_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('minutes_played');
            $table->timestamps();

            $table->unique(['nintendo_game_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nintendo_daily_records');
    }
};
