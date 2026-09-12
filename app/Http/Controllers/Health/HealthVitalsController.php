<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use Illuminate\View\View;

final class HealthVitalsController extends Controller
{
    public function index(): View
    {
        $history = HealthEntry::where(function ($q) {
            $q->whereNotNull('resting_heart_rate')
              ->orWhereNotNull('hrv')
              ->orWhereNotNull('respiratory_rate')
              ->orWhereNotNull('weight_kg');
        })
            ->orderByDesc('date')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        $latest = $history->last();

        $avgRestingHr      = $history->whereNotNull('resting_heart_rate')->avg('resting_heart_rate');
        $avgHrv            = $history->whereNotNull('hrv')->avg('hrv');
        $avgRespiratoryRate = $history->whereNotNull('respiratory_rate')->avg('respiratory_rate');
        $latestWeight      = HealthEntry::whereNotNull('weight_kg')->orderByDesc('date')->first();

        $hrChart = [
            'labels' => $history->whereNotNull('resting_heart_rate')->map(fn ($e) => $e->date->format('d M'))->values()->all(),
            'values' => $history->whereNotNull('resting_heart_rate')->pluck('resting_heart_rate')->values()->all(),
        ];

        $hrvChart = [
            'labels' => $history->whereNotNull('hrv')->map(fn ($e) => $e->date->format('d M'))->values()->all(),
            'values' => $history->whereNotNull('hrv')->map(fn ($e) => round((float) $e->hrv, 1))->values()->all(),
        ];

        $weightHistory = HealthEntry::whereNotNull('weight_kg')->orderByDesc('date')->limit(60)->get()->reverse()->values();

        $weightChart = [
            'labels' => $weightHistory->map(fn ($e) => $e->date->format('d M'))->all(),
            'values' => $weightHistory->pluck('weight_kg')->all(),
        ];

        return view('pages.health.vitals', compact(
            'history', 'latest', 'latestWeight',
            'avgRestingHr', 'avgHrv', 'avgRespiratoryRate',
            'hrChart', 'hrvChart', 'weightChart',
        ));
    }
}
