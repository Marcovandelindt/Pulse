<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tv_series', function (Blueprint $table) {
            $table->decimal('user_rating', 3, 1)->nullable()->after('vote_average');
        });
    }

    public function down(): void
    {
        Schema::table('tv_series', function (Blueprint $table) {
            $table->dropColumn('user_rating');
        });
    }
};
