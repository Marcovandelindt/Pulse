<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_station_trophies', function (Blueprint $table) {
            $table->string('progress_value')->nullable()->after('earned_rate');
            $table->string('progress_target')->nullable()->after('progress_value');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_trophies', function (Blueprint $table) {
            $table->dropColumn(['progress_value', 'progress_target']);
        });
    }
};
