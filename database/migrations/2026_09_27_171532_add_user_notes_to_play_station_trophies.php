<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_station_trophies', function (Blueprint $table) {
            $table->text('user_notes')->nullable()->after('progress_target');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_trophies', function (Blueprint $table) {
            $table->dropColumn('user_notes');
        });
    }
};
