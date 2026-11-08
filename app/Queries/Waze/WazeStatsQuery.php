<?php

declare(strict_types=1);

namespace App\Queries\Waze;

use App\Models\WazeDrive;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class WazeStatsQuery
{
    /** @return array<string, mixed> */
    public function handle(): array
    {
        $now = Carbon::now();

        $thisMonth = WazeDrive::query()
            ->whereYear('driven_at', $now->year)
            ->whereMonth('driven_at', $now->month);

        $drivesThisMonth = (int) $thisMonth->count();
        $kmThisMonth = (float) $thisMonth->sum('distance_km');

        $topDestination = WazeDrive::query()
            ->whereNotNull('destination_name')
            ->selectRaw('destination_name, COUNT(*) as total')
            ->groupBy('destination_name')
            ->orderByDesc('total')
            ->value('destination_name');

        $perMonth = WazeDrive::query()
            ->orderBy('driven_at')
            ->get(['driven_at'])
            ->groupBy(fn (WazeDrive $drive): string => $drive->driven_at->format('Y-m'))
            ->map(fn (Collection $group, string $month): array => [
                'month' => $month,
                'total' => $group->count(),
            ])
            ->values();

        return [
            'drivesThisMonth' => $drivesThisMonth,
            'kmThisMonth' => round($kmThisMonth, 1),
            'topDestination' => $topDestination,
            'perMonth' => $perMonth,
        ];
    }
}
