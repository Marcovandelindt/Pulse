<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('health_sleep', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->dateTime('sleep_start')->unique();
            $table->dateTime('sleep_end');
            $table->dateTime('in_bed_start')->nullable();
            $table->dateTime('in_bed_end')->nullable();
            $table->unsignedSmallInteger('total_sleep_minutes');
            $table->unsignedSmallInteger('awake_minutes')->nullable();
            $table->unsignedSmallInteger('rem_minutes')->nullable();
            $table->unsignedSmallInteger('deep_minutes')->nullable();
            $table->unsignedSmallInteger('core_minutes')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_sleep');
    }
};
