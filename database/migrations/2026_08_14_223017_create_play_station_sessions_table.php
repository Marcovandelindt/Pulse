<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_station_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_station_game_id')->constrained()->cascadeOnDelete();
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->integer('duration_minutes');
            $table->timestamps();

            $table->unique(['play_station_game_id', 'started_at']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_station_sessions');
    }
};
