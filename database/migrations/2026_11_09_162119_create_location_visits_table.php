<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('place_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id')->default('');
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
            $table->decimal('centroid_latitude', 10, 7);
            $table->decimal('centroid_longitude', 10, 7);
            $table->unsignedInteger('point_count');
            $table->timestamps();

            $table->index(['device_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_visits');
    }
};
