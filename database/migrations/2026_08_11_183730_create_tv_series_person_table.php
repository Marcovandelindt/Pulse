<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_series_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tv_series_id')->constrained('tv_series')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('character')->nullable();
            $table->string('department')->nullable();
            $table->string('job')->nullable();
            $table->unsignedSmallInteger('cast_order')->nullable();
            $table->unsignedSmallInteger('episode_count')->nullable();
            $table->timestamps();

            $table->unique(['tv_series_id', 'person_id', 'job']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_series_person');
    }
};
