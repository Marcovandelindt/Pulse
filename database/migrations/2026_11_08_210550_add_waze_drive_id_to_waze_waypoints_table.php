<?php

declare(strict_types=1);

use App\Models\WazeDrive;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waze_waypoints', function (Blueprint $table): void {
            $table->foreignId('waze_drive_id')->nullable()->after('route_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('waze_waypoints', function (Blueprint $table): void {
            $table->dropForeignIdFor(WazeDrive::class);
            $table->dropColumn('waze_drive_id');
        });
    }
};
