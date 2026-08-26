<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insight_insight', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_insight_id')->constrained('insights')->cascadeOnDelete();
            $table->unique(['insight_id', 'related_insight_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insight_insight');
    }
};
