<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_station_wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform')->default('PS5');
            $table->decimal('price', 6, 2)->nullable();
            $table->string('psn_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_station_wishlist_items');
    }
};
