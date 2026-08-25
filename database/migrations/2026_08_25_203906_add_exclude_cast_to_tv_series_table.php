<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tv_series', function (Blueprint $table) {
            $table->boolean('exclude_cast')->default(false)->after('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::table('tv_series', function (Blueprint $table) {
            $table->dropColumn('exclude_cast');
        });
    }
};
