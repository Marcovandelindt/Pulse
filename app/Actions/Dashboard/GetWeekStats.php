<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\EpisodeWatch;
use App\Models\Expense;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;

final class GetWeekStats
{
    /**
     * @return array<string, string|null>
     */
    public function handle(): array
    {
        $stepsThisWeek = (int) HealthEntry::withSteps()->thisWeek()->sum('steps');

        $episodeMinutes = (int) EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->whereBetween('episode_watches.watched_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotNull('episode_watches.watched_at')
            ->sum('tv_episodes.runtime');

        $movieMinutes = (int) MovieWatch::join('movies', 'movie_watches.movie_id', '=', 'movies.id')
            ->whereBetween('movie_watches.watched_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotNull('movie_watches.watched_at')
            ->sum('movies.runtime');

        $playtimeMinutes = (int) PlayStationSession::thisWeek()->sum('duration_minutes');
        $spendingThisWeek = (float) Expense::thisWeek()->sum('amount');
        $tracksThisWeek = Play::whereBetween('played_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return [
            'stepsThisWeek' => $stepsThisWeek > 0 ? number_format($stepsThisWeek) : null,
            'watchtimeThisWeek' => $this->formatMinutes($episodeMinutes + $movieMinutes),
            'playtimeThisWeek' => $this->formatMinutes($playtimeMinutes),
            'tracksThisWeek' => $tracksThisWeek > 0 ? number_format($tracksThisWeek) : null,
            'spendingThisWeek' => $spendingThisWeek > 0 ? '€ '.number_format($spendingThisWeek, 2, ',', '.') : null,
        ];
    }

    private function formatMinutes(int $minutes): ?string
    {
        if ($minutes === 0) {
            return null;
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}m";
        }

        return $h > 0 ? "{$h}h" : "{$m}m";
    }
}
