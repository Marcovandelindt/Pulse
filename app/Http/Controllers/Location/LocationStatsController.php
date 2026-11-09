<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\LocationTrip;
use App\Models\LocationVisit;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LocationStatsController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->string('period', 'month')->toString();

        $from = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            '3months' => now()->subMonths(3)->startOfDay(),
            'year' => now()->startOfYear(),
            default => null,
        };

        $tripQuery = LocationTrip::query();

        if ($from !== null) {
            $tripQuery->where('started_at', '>=', $from);
        }

        $trips = $tripQuery->get();

        $totalKm = round($trips->sum('distance_meters') / 1000, 1);
        $totalTrips = $trips->count();

        /** @var array<string, float> $byMotion */
        $byMotion = $trips
            ->groupBy('dominant_motion')
            ->map(fn ($group): float => round((float) $group->sum('distance_meters') / 1000, 1))
            ->sortDesc()
            ->toArray();

        $visitQuery = LocationVisit::query();

        if ($from !== null) {
            $visitQuery->where('started_at', '>=', $from);
        }

        $totalVisits = $visitQuery->count();

        $topPlaces = Place::query()
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get();

        $uniquePlaces = Place::query()->count();

        return view('pages.location.stats', [
            'period' => $period,
            'totalKm' => $totalKm,
            'totalTrips' => $totalTrips,
            'totalVisits' => $totalVisits,
            'byMotion' => $byMotion,
            'topPlaces' => $topPlaces,
            'uniquePlaces' => $uniquePlaces,
        ]);
    }
}
