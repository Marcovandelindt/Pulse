<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('step_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('steps');
            $table->date('effective_from');
            $table->timestamps();

            $table->unique('effective_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_goals');
    }
};
