<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_station_games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform');
            $table->string('image_url')->nullable();
            $table->decimal('hours', 8, 2)->default(0);
            $table->integer('sessions')->default(0);
            $table->integer('avg_session_minutes')->default(0);
            $table->date('last_played_at')->nullable();
            $table->integer('trophies')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->string('psn_url')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->integer('manual_minutes')->default(0);
            $table->boolean('exclude_from_sync')->default(false);
            $table->string('backlog_status')->nullable();
            $table->decimal('user_rating', 3, 1)->nullable();
            $table->decimal('critic_rating', 3, 1)->nullable();
            $table->string('play_mode')->nullable();
            $table->boolean('main_story_completed')->default(false);
            $table->timestamps();

            $table->unique(['name', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_station_games');
    }
};
