<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Models\PlayStationGame;
use App\Models\PlayStationSession;
use App\Services\PlayStation\PsnPresenceService;

final class PlayStationIndexQuery
{
    public function __construct(
        private readonly PsnPresenceService $presenceService,
    ) {}

    /** @return array<string, mixed> */
    public function handle(string $sort, ?string $platform, string $search): array
    {
        $baseQuery = PlayStationGame::query();

        if ($platform) {
            $baseQuery->where('platform', $platform);
        }

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        $totalMinutes = (int) (clone $baseQuery)
            ->join('play_station_sessions', 'play_station_games.id', '=', 'play_station_sessions.play_station_game_id')
            ->whereRaw('(play_station_games.released_at IS NULL OR play_station_sessions.started_at >= play_station_games.released_at)')
            ->sum('play_station_sessions.duration_minutes');

        $totalHours    = round($totalMinutes / 60, 1);
        $totalGames    = (clone $baseQuery)->count();
        $totalSessions = (clone $baseQuery)->sum('sessions');

        $sorted = match ($sort) {
            'name'        => (clone $baseQuery)->orderBy('name'),
            'last_played' => (clone $baseQuery)->orderByDesc('last_played_at'),
            'completion'  => (clone $baseQuery)->orderByDesc('completion_percentage'),
            default       => (clone $baseQuery)->orderByDesc('hours'),
        };

        $games = $sorted->withCount([
            'trophyList',
            'trophyList as earned_trophy_count' => fn ($q) => $q->where('is_earned', true),
        ])->withSum(['playSessions as filtered_minutes' => fn ($q) => $q->whereRaw(
            '(play_station_games.released_at IS NULL OR play_station_sessions.started_at >= play_station_games.released_at)'
        )], 'duration_minutes')->paginate(24)->withQueryString();

        $recentSessions = PlayStationSession::with('game')
            ->whereHas('game')
            ->latest('started_at')
            ->limit(5)
            ->get();

        $fallbacks  = ['ps1.jpg', 'ps2.webp', 'ps3.jpg', 'ps4.jpg', 'ps5.jpg'];
        $sleepItems = PlayStationGame::orderByDesc('hours')
            ->get(['id', 'name', 'display_name', 'platform', 'image_url', 'hours', 'completion_percentage', 'last_played_at'])
            ->map(fn ($g) => [
                'title'       => $g->label,
                'platform'    => $g->platform,
                'image_url'   => $g->image_url ?? '/images/playstation/'.$fallbacks[$g->id % 5],
                'hours'       => round((float) $g->hours, 1),
                'completion'  => round((float) $g->completion_percentage, 0),
                'last_played' => $g->last_played_at?->format('d M Y'),
                'url'         => route('playstation.show', $g),
            ]);

        try {
            $currentGame = $this->presenceService->getCurrentGame();
        } catch (\Throwable) {
            $currentGame = null;
        }

        return compact(
            'games', 'sort', 'platform', 'search',
            'totalHours', 'totalGames', 'totalSessions',
            'recentSessions', 'sleepItems', 'currentGame',
        );
    }
}
