<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waze;

use App\Http\Controllers\Controller;
use App\Models\WazeWaypoint;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class WazeHeatmapController extends Controller
{
    public function index(): View
    {
        return view('pages.waze.heatmap');
    }

    public function data(): JsonResponse
    {
        $routes = WazeWaypoint::query()
            ->select(['route_id', 'lat', 'lon'])
            ->orderBy('route_id')
            ->orderBy('id')
            ->get()
            ->groupBy('route_id')
            ->map(fn ($points) => $points->map(fn (WazeWaypoint $w): array => [$w->lat, $w->lon])->values())
            ->values();

        return response()->json($routes);
    }
}
