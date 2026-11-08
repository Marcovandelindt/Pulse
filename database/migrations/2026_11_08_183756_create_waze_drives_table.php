<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waze_drives', function (Blueprint $table) {
            $table->id();
            $table->dateTime('driven_at');
            $table->string('origin_name')->nullable();
            $table->decimal('origin_lat', 10, 6)->nullable();
            $table->decimal('origin_lon', 10, 6)->nullable();
            $table->string('destination_name')->nullable();
            $table->decimal('destination_lat', 10, 6)->nullable();
            $table->decimal('destination_lon', 10, 6)->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->timestamps();

            $table->unique('driven_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waze_drives');
    }
};
