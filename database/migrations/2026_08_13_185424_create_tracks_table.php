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
            $table->string('spotify_id')->unique();
            $table->foreignId('album_id')->constrained('albums')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('track_number');
            $table->unsignedTinyInteger('disc_number')->default(1);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('preview_url')->nullable();
            $table->boolean('is_explicit')->default(false);
            $table->timestamps();

            $table->unique(['album_id', 'track_number', 'disc_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
