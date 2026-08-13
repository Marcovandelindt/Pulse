<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();
            $table->timestamp('played_at');
            $table->string('source')->default('spotify');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->unique(['track_id', 'played_at']);
            $table->index('played_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plays');
    }
};
