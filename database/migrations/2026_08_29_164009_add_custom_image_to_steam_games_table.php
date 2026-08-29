<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('steam_games', function (Blueprint $table) {
            $table->string('custom_image_url')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('steam_games', function (Blueprint $table) {
            $table->dropColumn('custom_image_url');
        });
    }
};
