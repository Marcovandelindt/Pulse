<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cod_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cod_map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cod_mode_id')->constrained()->cascadeOnDelete();
            $table->string('result');
            $table->unsignedSmallInteger('team_score');
            $table->unsignedSmallInteger('opponent_score');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('kills');
            $table->unsignedSmallInteger('deaths');
            $table->decimal('kd_ratio', 5, 2);
            $table->unsignedSmallInteger('objective_score')->nullable();
            $table->unsignedInteger('damage');
            $table->foreignId('top_weapon_id')->nullable()->constrained('cod_weapons')->nullOnDelete();
            $table->decimal('weapon_accuracy', 5, 2)->nullable();
            $table->unsignedSmallInteger('weapon_kills')->nullable();
            $table->date('estimated_date');
            $table->string('fingerprint')->unique();
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_matches');
    }
};
