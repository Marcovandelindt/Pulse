<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id')->unique();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->date('release_date')->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->unsignedSmallInteger('track_count')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('genres')->nullable();
            $table->string('album_type')->nullable();
            $table->string('label')->nullable();
            $table->unsignedInteger('listen_count')->default(0);
            $table->timestamp('last_listened_at')->nullable();
            $table->timestamp('first_listened_at')->nullable();
            $table->timestamps();

            $table->index('artist_id');
            $table->index('last_listened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
