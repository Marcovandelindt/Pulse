<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->string('psnprofiles_slug')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('play_station_games', function (Blueprint $table) {
            $table->dropColumn('psnprofiles_slug');
        });
    }
};
