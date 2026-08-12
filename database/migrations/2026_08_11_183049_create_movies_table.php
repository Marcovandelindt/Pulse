<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id')->unique();
            $table->string('title');
            $table->string('original_title');
            $table->text('overview')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->date('release_date')->nullable();
            $table->unsignedSmallInteger('runtime')->nullable();
            $table->decimal('vote_average', 3, 1)->nullable();
            $table->json('genres')->nullable();
            $table->string('original_language', 10)->nullable();
            $table->unsignedInteger('watch_count')->default(0);
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamp('first_watched_at')->nullable();
            $table->timestamps();

            $table->index('last_watched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
