<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_trips', function (Blueprint $table): void {
            $table->id();
            $table->string('device_id')->default('');
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->float('distance_meters');
            $table->string('dominant_motion');           // driving | walking | cycling | other
            $table->unsignedInteger('point_count');
            $table->json('polyline');                    // [[lat, lon], …] downsampled to max N points
            $table->timestamps();

            $table->index(['device_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_trips');
    }
};
