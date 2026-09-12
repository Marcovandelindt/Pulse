<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\HealthEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HealthEntry extends Model
{
    /** @use HasFactory<HealthEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'steps',
        'heart_rate_avg',
        'heart_rate_min',
        'heart_rate_max',
        'resting_heart_rate',
        'hrv',
        'respiratory_rate',
        'active_calories',
        'basal_calories',
        'exercise_minutes',
        'stand_hours',
        'flights_climbed',
        'distance_km',
        'weight_kg',
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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date'  => 'date',
            'steps' => 'integer',
        ];
    }

    public function meetsStepGoal(int $goal): bool
    {
        return $this->steps !== null && $this->steps >= $goal;
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('date')->limit($limit);
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeLastWeek(Builder $query): Builder
    {
        return $query->whereBetween('date', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);
    }

    public function scopeLastMonth(Builder $query): Builder
    {
        return $query->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date', now()->subMonth()->year);
    }

    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeWithSteps(Builder $query): Builder
    {
        return $query->whereNotNull('steps');
    }
}
