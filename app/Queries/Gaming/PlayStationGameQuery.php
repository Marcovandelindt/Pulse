<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Models\PlayStationGame;
use Carbon\Carbon;

final class PlayStationGameQuery
{
    /** @return array<string, mixed> */
    public function handle(PlayStationGame $game, ?string $from = null, ?string $to = null): array
    {
        $game->load('playSessions', 'categories', 'trophyList', 'tracks.artists');

        $fromDt = $from ? Carbon::parse($from) : null;
        $toDt   = $to   ? Carbon::parse($to)   : null;

        // Include sessions that overlap the range, not just those fully within it.
        // TIMESTAMPADD checks session end time (started_at + duration_minutes).
        $sessionsQuery = $game->playSessions()
            ->when($game->released_at, fn ($q) => $q->whereDate('started_at', '>=', $game->released_at))
            ->when($fromDt, fn ($q) => $q->whereRaw(
                'TIMESTAMPADD(MINUTE, duration_minutes, started_at) > ?',
                [$fromDt->toDateTimeString()]
            ))
            ->when($toDt, fn ($q) => $q->where('started_at', '<', $toDt));

        // Per-session clamping in PHP so partial sessions at the boundaries are counted correctly.
        $periodMinutes = null;
        $sessionCaps   = [];

        if ($fromDt || $toDt) {
            $overlapSessions = (clone $sessionsQuery)->get(['id', 'started_at', 'duration_minutes']);
            $total = 0;

            foreach ($overlapSessions as $s) {
                $sessionEnd   = $s->started_at->copy()->addMinutes($s->duration_minutes);
                $clampedStart = ($fromDt && $s->started_at < $fromDt) ? $fromDt->copy() : $s->started_at->copy();
                $clampedEnd   = ($toDt   && $sessionEnd   > $toDt)   ? $toDt->copy()   : $sessionEnd;
                $capped       = (int) $clampedStart->diffInMinutes($clampedEnd);
                $total       += $capped;

                if ($capped < $s->duration_minutes) {
                    $sessionCaps[$s->id] = $capped;
                }
            }

            $periodMinutes = $total;
        }

        $recentSessions = (clone $sessionsQuery)
            ->latest('started_at')
            ->paginate(20)
            ->withQueryString();

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

        return compact('game', 'recentSessions', 'monthlyStats', 'from', 'to', 'periodMinutes', 'sessionCaps');
    }
}
