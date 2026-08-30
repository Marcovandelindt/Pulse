<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gaming_presences', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->unsignedBigInteger('game_id')->nullable();
            $table->string('game_name');
            $table->string('image_url')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gaming_presences');
    }
};
