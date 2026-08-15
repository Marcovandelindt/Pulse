<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movie_watches', function (Blueprint $table): void {
            $table->decimal('rating', 3, 1)->unsigned()->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('movie_watches', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating')->nullable()->change();
        });
    }
};
