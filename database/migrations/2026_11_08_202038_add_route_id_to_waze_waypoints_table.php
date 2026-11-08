<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waze_waypoints', function (Blueprint $table): void {
            $table->unsignedInteger('route_id')->after('id')->default(0);
            $table->index('route_id');
        });
    }

    public function down(): void
    {
        Schema::table('waze_waypoints', function (Blueprint $table): void {
            $table->dropIndex(['route_id']);
            $table->dropColumn('route_id');
        });
    }
};
