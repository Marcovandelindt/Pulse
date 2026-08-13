<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('album_listens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('albums')->cascadeOnDelete();
            $table->timestamp('listened_at')->nullable();
            $table->boolean('year_only')->default(false);
            $table->text('notes')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->timestamps();

            $table->index('album_id');
            $table->index('listened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_listens');
    }
};
