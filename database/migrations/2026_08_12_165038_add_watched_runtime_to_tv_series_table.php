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
            $table->unsignedInteger('watched_runtime_minutes')->default(0)->after('episodes_watched');
        });
    }

    public function down(): void
    {
        Schema::table('tv_series', function (Blueprint $table) {
            $table->dropColumn('watched_runtime_minutes');
        });
    }
};
