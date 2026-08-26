<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insight_pattern', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pattern_id')->constrained()->cascadeOnDelete();
            $table->unique(['insight_id', 'pattern_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insight_pattern');
    }
};
