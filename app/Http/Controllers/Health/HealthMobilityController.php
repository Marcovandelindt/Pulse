<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use Illuminate\View\View;

final class HealthMobilityController extends Controller
{
    public function index(): View
    {
        $history = HealthEntry::where(function ($q) {
            $q->whereNotNull('walking_speed_kmh')
              ->orWhereNotNull('walking_step_length_cm')
              ->orWhereNotNull('walking_asymmetry_pct')
              ->orWhereNotNull('walking_double_support_pct')
              ->orWhereNotNull('stair_speed_up')
              ->orWhereNotNull('stair_speed_down')
              ->orWhereNotNull('time_in_daylight_minutes');
        })
            ->orderByDesc('date')
            ->limit(90)
            ->get()
            ->reverse()
            ->values();

        $latest = $history->last();

        $avgWalkingSpeed       = $history->whereNotNull('walking_speed_kmh')->avg('walking_speed_kmh');
        $avgStepLength         = $history->whereNotNull('walking_step_length_cm')->avg('walking_step_length_cm');
        $avgAsymmetry          = $history->whereNotNull('walking_asymmetry_pct')->avg('walking_asymmetry_pct');
        $avgDoubleSupport      = $history->whereNotNull('walking_double_support_pct')->avg('walking_double_support_pct');
        $avgDaylight           = $history->whereNotNull('time_in_daylight_minutes')->avg('time_in_daylight_minutes');

        $speedChart = [
            'labels' => $history->whereNotNull('walking_speed_kmh')->map(fn ($e) => $e->date->format('d M'))->values()->all(),
            'values' => $history->whereNotNull('walking_speed_kmh')->map(fn ($e) => round((float) $e->walking_speed_kmh, 2))->values()->all(),
        ];

        $stepLengthChart = [
            'labels' => $history->whereNotNull('walking_step_length_cm')->map(fn ($e) => $e->date->format('d M'))->values()->all(),
            'values' => $history->whereNotNull('walking_step_length_cm')->map(fn ($e) => round((float) $e->walking_step_length_cm, 1))->values()->all(),
        ];

        $daylightChart = [
            'labels' => $history->whereNotNull('time_in_daylight_minutes')->map(fn ($e) => $e->date->format('d M'))->values()->all(),
            'values' => $history->whereNotNull('time_in_daylight_minutes')->pluck('time_in_daylight_minutes')->values()->all(),
        ];

        return view('pages.health.mobility', compact(
            'history', 'latest',
            'avgWalkingSpeed', 'avgStepLength', 'avgAsymmetry', 'avgDoubleSupport', 'avgDaylight',
            'speedChart', 'stepLengthChart', 'daylightChart',
        ));
    }
}
