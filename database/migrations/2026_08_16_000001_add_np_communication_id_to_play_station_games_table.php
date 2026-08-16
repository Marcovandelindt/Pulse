<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->string('np_communication_id')->nullable()->after('psn_url');
            $table->string('np_service_name')->nullable()->after('np_communication_id');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->dropColumn(['np_communication_id', 'np_service_name']);
        });
    }
};
