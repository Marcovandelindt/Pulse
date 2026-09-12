<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use Illuminate\View\View;

final class HealthActivityController extends Controller
{
    public function index(): View
    {
        // Last 30 days with active calories for the chart
        $calorieHistory = HealthEntry::whereNotNull('active_calories')
            ->orderByDesc('date')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        $calorieChart = [
            'labels' => $calorieHistory->map(fn ($e) => $e->date->format('d M'))->all(),
            'values' => $calorieHistory->pluck('active_calories')->all(),
        ];

        // Stat cards: averages over last 30 days
        $avgActiveCalories = (int) round($calorieHistory->avg('active_calories') ?? 0);
        $totalActiveCalories = $calorieHistory->sum('active_calories');

        // Most recent Apple Watch entry (has exercise/stand data)
        $latestAppleWatch = HealthEntry::whereNotNull('exercise_minutes')
            ->orWhereNotNull('stand_hours')
            ->orderByDesc('date')
            ->first();

        // Apple Watch history (exercise + stand)
        $appleWatchHistory = HealthEntry::where(function ($q) {
            $q->whereNotNull('exercise_minutes')->orWhereNotNull('stand_hours');
        })
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        return view('pages.health.activity', compact(
            'calorieChart', 'avgActiveCalories', 'totalActiveCalories',
            'latestAppleWatch', 'appleWatchHistory',
        ));
    }
}
