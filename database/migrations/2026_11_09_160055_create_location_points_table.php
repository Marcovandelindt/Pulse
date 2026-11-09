<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_points', function (Blueprint $table): void {
            $table->id();
            // Default '' so the composite unique index works when device_id is absent
            $table->string('device_id')->default('');
            $table->timestamp('recorded_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('altitude')->nullable();
            $table->float('speed')->nullable();             // m/s
            $table->float('horizontal_accuracy')->nullable();
            $table->json('motion')->nullable();
            $table->float('battery_level')->nullable();
            $table->json('raw');                            // full Overland properties blob
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['device_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_points');
    }
};
