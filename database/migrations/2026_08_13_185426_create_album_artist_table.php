<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('album_artist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('albums')->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['album_id', 'artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_artist');
    }
};
