<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episode_watches', function (Blueprint $table) {
            $table->boolean('month_year_only')->default(false)->after('year_only');
        });
    }

    public function down(): void
    {
        Schema::table('episode_watches', function (Blueprint $table) {
            $table->dropColumn('month_year_only');
        });
    }
};
