<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Models\PlayStationGame;

final class PlayStationGameQuery
{
    /** @return array<string, mixed> */
    public function handle(PlayStationGame $game): array
    {
        $game->load('playSessions', 'categories', 'trophyList', 'tracks.artists');

        $recentSessions = $game->playSessions()
            ->when($game->released_at, fn ($q) => $q->whereDate('started_at', '>=', $game->released_at))
            ->latest('started_at')
            ->paginate(20);

        $monthlyStats = $game->playSessions()
            ->when($game->released_at, fn ($q) => $q->whereDate('started_at', '>=', $game->released_at))
            ->selectRaw("DATE_FORMAT(started_at, '%Y-%m') as month, SUM(duration_minutes) as total_minutes")
            ->groupByRaw("DATE_FORMAT(started_at, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month'         => $row->month,
                'total_minutes' => (int) $row->total_minutes,
                'hours'         => round($row->total_minutes / 60, 1),
            ]);

        return compact('game', 'recentSessions', 'monthlyStats');
    }
}
