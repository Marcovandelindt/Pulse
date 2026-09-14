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
        Schema::table('nintendo_games', function (Blueprint $table) {
            $table->unsignedBigInteger('igdb_id')->nullable()->unique()->after('application_id');
            $table->json('genres')->nullable()->after('image_url');
            $table->date('released_at')->nullable()->after('genres');
        });
    }

    public function down(): void
    {
        Schema::table('nintendo_games', function (Blueprint $table) {
            $table->dropColumn(['igdb_id', 'genres', 'released_at']);
        });
    }
};
