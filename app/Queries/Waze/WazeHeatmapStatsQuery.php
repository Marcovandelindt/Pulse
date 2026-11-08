<?php

declare(strict_types=1);

namespace App\Queries\Waze;

use App\Models\WazeDrive;
use App\Models\WazeWaypoint;

final class WazeHeatmapStatsQuery
{
    // ~1 km² grid cells covering NL land area (41,543 km² / 0.763 km² per 0.01° cell at 52° lat)
    private const int NL_LAND_CELLS = 54436;

    /** @return array{totalKm: float, totalDrives: int, totalHours: float, discoveredPercent: float, longestKm: float} */
    public function get(): array
    {
        $totalKm = round((float) WazeDrive::query()->sum('distance_km'), 1);
        $totalDrives = WazeDrive::query()->count();
        $longestKm = round((float) (WazeDrive::query()->max('distance_km') ?? 0), 1);

        $totalMinutes = WazeDrive::query()
            ->whereNotNull('ended_at')
            ->get(['driven_at', 'ended_at'])
            ->sum(fn (WazeDrive $d): int => (int) $d->driven_at->diffInMinutes($d->ended_at));

        $uniqueCells = WazeWaypoint::query()
            ->select(['lat', 'lon'])
            ->get()
            ->map(fn (WazeWaypoint $w): string => floor($w->lat * 100).','.floor($w->lon * 100))
            ->unique()
            ->count();

        return [
            'totalKm' => $totalKm,
            'totalDrives' => $totalDrives,
            'totalHours' => round($totalMinutes / 60, 1),
            'discoveredPercent' => round($uniqueCells / self::NL_LAND_CELLS * 100, 2),
            'longestKm' => $longestKm,
        ];
    }
}
