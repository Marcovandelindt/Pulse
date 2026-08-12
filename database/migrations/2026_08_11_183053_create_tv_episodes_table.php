<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tv_season_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tmdb_id')->unique();
            $table->string('name');
            $table->text('overview')->nullable();
            $table->unsignedSmallInteger('episode_number');
            $table->date('air_date')->nullable();
            $table->unsignedSmallInteger('runtime')->nullable();
            $table->decimal('vote_average', 3, 1)->nullable();
            $table->timestamps();

            $table->unique(['tv_season_id', 'episode_number']);
            $table->index('tv_season_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_episodes');
    }
};
