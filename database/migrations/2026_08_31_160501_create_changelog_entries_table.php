<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelog_entries', function (Blueprint $table) {
            $table->id();
            $table->string('commit_hash', 40)->unique()->nullable();
            $table->string('type', 20);
            $table->string('scope', 50)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('files_changed')->nullable();
            $table->json('stats')->nullable();
            $table->timestamp('committed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_entries');
    }
};
