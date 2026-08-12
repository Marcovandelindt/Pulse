<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tv_series_id')->constrained('tv_series')->cascadeOnDelete();
            $table->unsignedInteger('tmdb_id')->unique();
            $table->string('name');
            $table->text('overview')->nullable();
            $table->string('poster_path')->nullable();
            $table->unsignedTinyInteger('season_number');
            $table->date('air_date')->nullable();
            $table->unsignedInteger('episode_count')->default(0);
            $table->unsignedInteger('episodes_watched')->default(0);
            $table->timestamps();

            $table->unique(['tv_series_id', 'season_number']);
            $table->index('tv_series_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_seasons');
    }
};
