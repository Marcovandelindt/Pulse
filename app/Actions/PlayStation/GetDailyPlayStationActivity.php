<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationSession;
use Illuminate\Support\Collection;

final class GetDailyPlayStationActivity
{
    public function handle(string $date): Collection
    {
        return PlayStationSession::with('game')
            ->whereDate('started_at', $date)
            ->get()
            ->groupBy('play_station_game_id')
            ->map(function ($sessions) {
                $game = $sessions->first()->game;

                return [
                    'game'          => $game->name,
                    'platform'      => $game->platform,
                    'total_minutes' => $sessions->sum('duration_minutes'),
                    'session_count' => $sessions->count(),
                ];
            })
            ->values();
    }
}
