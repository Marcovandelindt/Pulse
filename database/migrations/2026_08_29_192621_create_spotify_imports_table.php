<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spotify_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_path');
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_entries')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('synced')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spotify_imports');
    }
};
