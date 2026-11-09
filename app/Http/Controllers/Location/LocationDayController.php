<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\LocationTrip;
use App\Models\LocationVisit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LocationDayController extends Controller
{
    public function index(Request $request): View
    {
        $dateString = $request->string('date', today()->toDateString())->toString();

        try {
            $day = Carbon::parse($dateString);
        } catch (\Exception) {
            $day = today();
        }

        return view('pages.location.index', [
            'date' => $day->toDateString(),
            'prevDate' => $day->copy()->subDay()->toDateString(),
            'nextDate' => $day->copy()->addDay()->toDateString(),
            'isToday' => $day->isToday(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $dateString = $request->string('date', today()->toDateString())->toString();

        try {
            $day = Carbon::parse($dateString);
        } catch (\Exception) {
            $day = today();
        }

        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();

        $trips = LocationTrip::query()
            ->where('started_at', '<=', $to)
            ->where('ended_at', '>=', $from)
            ->orderBy('started_at')
            ->get()
            ->map(fn (LocationTrip $trip): array => [
                'id' => $trip->id,
                'type' => 'trip',
                'started_at' => $trip->started_at->format('H:i'),
                'ended_at' => $trip->ended_at->format('H:i'),
                'duration_minutes' => (int) $trip->started_at->diffInMinutes($trip->ended_at),
                'distance_km' => round($trip->distance_meters / 1000, 2),
                'dominant_motion' => $trip->dominant_motion,
                'point_count' => $trip->point_count,
                'polyline' => $trip->polyline,
            ]);

        $visits = LocationVisit::with('place')
            ->where('started_at', '<=', $to)
            ->where('ended_at', '>=', $from)
            ->orderBy('started_at')
            ->get()
            ->map(fn (LocationVisit $visit): array => [
                'id' => $visit->id,
                'type' => 'visit',
                'started_at' => $visit->started_at->format('H:i'),
                'ended_at' => $visit->ended_at->format('H:i'),
                'duration_minutes' => (int) $visit->started_at->diffInMinutes($visit->ended_at),
                'place_id' => $visit->place_id,
                'place_name' => $visit->place?->name,
                'lat' => $visit->centroid_latitude,
                'lon' => $visit->centroid_longitude,
            ]);

        return response()->json([
            'trips' => $trips,
            'visits' => $visits,
        ]);
    }
}
