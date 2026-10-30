<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cod_match_screenshot', function (Blueprint $table) {
            $table->foreignId('cod_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cod_screenshot_id')->constrained()->cascadeOnDelete();
            $table->primary(['cod_match_id', 'cod_screenshot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_match_screenshot');
    }
};
