<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_entries', function (Blueprint $table) {
            // Mobility
            $table->decimal('walking_speed_kmh', 5, 2)->nullable()->after('distance_km');
            $table->decimal('walking_step_length_cm', 5, 2)->nullable()->after('walking_speed_kmh');
            $table->decimal('walking_asymmetry_pct', 5, 2)->nullable()->after('walking_step_length_cm');
            $table->decimal('walking_double_support_pct', 5, 2)->nullable()->after('walking_asymmetry_pct');
            $table->decimal('stair_speed_up', 5, 3)->nullable()->after('walking_double_support_pct');
            $table->decimal('stair_speed_down', 5, 3)->nullable()->after('stair_speed_up');
            $table->integer('time_in_daylight_minutes')->nullable()->after('stair_speed_down');
            $table->decimal('walking_heart_rate_avg', 5, 1)->nullable()->after('time_in_daylight_minutes');

            // Hearing
            $table->decimal('headphone_audio_exposure_db', 5, 2)->nullable()->after('walking_heart_rate_avg');
            $table->decimal('environmental_audio_exposure_db', 5, 2)->nullable()->after('headphone_audio_exposure_db');
        });
    }

    public function down(): void
    {
        Schema::table('health_entries', function (Blueprint $table) {
            $table->dropColumn([
                'walking_speed_kmh',
                'walking_step_length_cm',
                'walking_asymmetry_pct',
                'walking_double_support_pct',
                'stair_speed_up',
                'stair_speed_down',
                'time_in_daylight_minutes',
                'walking_heart_rate_avg',
                'headphone_audio_exposure_db',
                'environmental_audio_exposure_db',
            ]);
        });
    }
};
