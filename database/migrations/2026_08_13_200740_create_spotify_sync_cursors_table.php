<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spotify_sync_cursors', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_played_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->unsignedInteger('plays_imported')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spotify_sync_cursors');
    }
};
