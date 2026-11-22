<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_station_game_todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_station_game_id')->constrained()->cascadeOnDelete();
            $table->string('title', 500);
            $table->text('body')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_station_game_todos');
    }
};
