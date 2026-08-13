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
            $table->string('spotify_album_id')->unique()->nullable();
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->date('release_date')->nullable();
            $table->string('album_type')->nullable();
            $table->unsignedSmallInteger('total_tracks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
