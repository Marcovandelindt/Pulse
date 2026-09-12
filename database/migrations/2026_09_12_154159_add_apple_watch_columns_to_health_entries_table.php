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
        Schema::table('health_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('heart_rate_avg')->nullable()->after('steps');
            $table->unsignedSmallInteger('heart_rate_min')->nullable()->after('heart_rate_avg');
            $table->unsignedSmallInteger('heart_rate_max')->nullable()->after('heart_rate_min');
            $table->unsignedSmallInteger('resting_heart_rate')->nullable()->after('heart_rate_max');
            $table->float('hrv')->nullable()->after('resting_heart_rate');
            $table->float('respiratory_rate')->nullable()->after('hrv');
            $table->unsignedSmallInteger('active_calories')->nullable()->after('respiratory_rate');
            $table->unsignedSmallInteger('basal_calories')->nullable()->after('active_calories');
            $table->unsignedSmallInteger('exercise_minutes')->nullable()->after('basal_calories');
            $table->unsignedSmallInteger('stand_hours')->nullable()->after('exercise_minutes');
            $table->unsignedSmallInteger('flights_climbed')->nullable()->after('stand_hours');
            $table->float('distance_km')->nullable()->after('flights_climbed');
            $table->float('weight_kg')->nullable()->after('distance_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_entries', function (Blueprint $table) {
            $table->dropColumn([
                'heart_rate_avg', 'heart_rate_min', 'heart_rate_max',
                'resting_heart_rate', 'hrv', 'respiratory_rate',
                'active_calories', 'basal_calories', 'exercise_minutes',
                'stand_hours', 'flights_climbed', 'distance_km', 'weight_kg',
            ]);
        });
    }
};
