<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\LocationPoint;
use App\Models\LocationTrip;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LocationHeatmapController extends Controller
{
    public function index(): View
    {
        return view('pages.location.heatmap');
    }

    public function data(Request $request): JsonResponse
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $query = LocationPoint::query()->select(['latitude', 'longitude']);

        if ($from !== '') {
            $query->where('recorded_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to !== '') {
            $query->where('recorded_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $points = $query->get()->map(fn (LocationPoint $p): array => [$p->latitude, $p->longitude]);

        $trips = LocationTrip::query();

        if ($from !== '') {
            $trips->where('started_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to !== '') {
            $trips->where('started_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $routes = $trips->orderBy('started_at')->get()->map(fn (LocationTrip $trip): array => [
            'id' => $trip->id,
            'dominant_motion' => $trip->dominant_motion,
            'polyline' => $trip->polyline,
        ]);

        return response()->json([
            'points' => $points,
            'routes' => $routes,
        ]);
    }
}
