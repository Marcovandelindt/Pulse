<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waze_waypoints', function (Blueprint $table): void {
            $table->id();
            $table->double('lat');
            $table->double('lon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waze_waypoints');
    }
};
