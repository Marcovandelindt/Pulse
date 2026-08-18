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
        Schema::create('steam_games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('steam_appid')->unique();
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->unsignedInteger('playtime_minutes')->default(0);
            $table->unsignedInteger('playtime_2weeks_minutes')->nullable();
            $table->timestamp('last_played_at')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('backlog_status')->nullable();
            $table->string('play_mode')->nullable();
            $table->boolean('main_story_completed')->default(false);
            $table->unsignedTinyInteger('user_rating')->nullable();
            $table->unsignedTinyInteger('critic_rating')->nullable();
            $table->timestamps();

            $table->index('playtime_minutes');
            $table->index('last_played_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('steam_games');
    }
};
