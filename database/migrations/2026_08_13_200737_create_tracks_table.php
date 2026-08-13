<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_track_id')->unique();
            $table->foreignId('album_id')->constrained('albums')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedTinyInteger('popularity')->nullable();
            $table->string('preview_url')->nullable();
            $table->string('spotify_uri')->nullable();
            $table->boolean('is_explicit')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
