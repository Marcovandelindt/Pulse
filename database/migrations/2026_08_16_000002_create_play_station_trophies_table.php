<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_station_trophies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_station_game_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('trophy_id');
            $table->string('trophy_group_id')->default('default');
            $table->string('name');
            $table->text('detail')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('type'); // bronze, silver, gold, platinum
            $table->boolean('is_earned')->default(false);
            $table->timestamp('earned_at')->nullable();
            $table->timestamps();

            $table->unique(['play_station_game_id', 'trophy_id', 'trophy_group_id'], 'ps_trophies_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_station_trophies');
    }
};
