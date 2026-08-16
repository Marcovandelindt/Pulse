<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('custom_backdrop_url')->nullable()->after('backdrop_path');
        });

        Schema::table('tv_series', function (Blueprint $table) {
            $table->string('custom_backdrop_url')->nullable()->after('backdrop_path');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('custom_backdrop_url');
        });

        Schema::table('tv_series', function (Blueprint $table) {
            $table->dropColumn('custom_backdrop_url');
        });
    }
};
