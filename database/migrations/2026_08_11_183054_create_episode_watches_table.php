<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episode_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tv_episode_id')->constrained()->cascadeOnDelete();
            $table->timestamp('watched_at')->nullable();
            $table->boolean('year_only')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();

            $table->index('tv_episode_id');
            $table->index('watched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_watches');
    }
};
