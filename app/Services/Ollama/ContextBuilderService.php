<?php

declare(strict_types=1);

namespace App\Services\Ollama;

use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ContextBuilderService
{
    public function buildSystemPrompt(): string
    {
        $sections = [
            $this->introduction(),
            $this->healthSummary(),
            $this->gamingSummary(),
            $this->musicSummary(),
            $this->mediaSummary(),
        ];

        return implode("\n\n", array_filter($sections));
    }

    private function introduction(): string
    {
        return implode("\n", [
            'You are Marco\'s personal data assistant inside his Pulse dashboard.',
            'You have access to his recent activity across health, gaming, music and media.',
            'Answer in the same language the user writes in (Dutch or English).',
            'Be concise and insightful. If data is missing or unclear, say so.',
            'Today is '.now()->format('l, j F Y').'.',
        ]);
    }

    private function healthSummary(): ?string
    {
        $entries = HealthEntry::query()
            ->whereNotNull('steps')
            ->where('date', '>=', now()->subDays(14)->toDateString())
            ->orderBy('date')
            ->get(['date', 'steps']);

        if ($entries->isEmpty()) {
            return null;
        }

        $avg  = (int) round((float) $entries->avg('steps'));
        $days = $entries->map(
            fn ($e) => Carbon::parse($e->date)->format('D d M').': '.number_format((int) $e->steps).' steps'
        )->join(', ');

        return "## Health (last 14 days)\n{$days}\nDaily average: {$avg} steps";
    }

    private function gamingSummary(): ?string
    {
        $sessions = PlayStationSession::query()
            ->with('game:id,display_name,name')
            ->where('started_at', '>=', now()->subDays(14))
            ->orderBy('started_at')
            ->get(['play_station_game_id', 'duration_minutes', 'started_at']);

        if ($sessions->isEmpty()) {
            return null;
        }

        $byGame = $sessions
            ->groupBy('play_station_game_id')
            ->map(function ($g) {
                $name    = $g->first()->game?->display_name ?? $g->first()->game?->name ?? 'Unknown';
                $minutes = (int) $g->sum('duration_minutes');
                return "{$name}: {$minutes} min";
            })
            ->values()
            ->join(', ');

        $total = (int) $sessions->sum('duration_minutes');

        return "## Gaming (last 14 days)\n{$byGame}\nTotal: {$total} min across {$sessions->count()} sessions";
    }

    private function musicSummary(): ?string
    {
        $total = Play::query()
            ->whereNotNull('played_at')
            ->where('played_at', '>=', now()->subDays(14))
            ->count();

        if ($total === 0) {
            return null;
        }

        $topArtists = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->join('artists', 'track_artists.artist_id', '=', 'artists.id')
            ->whereNotNull('plays.played_at')
            ->where('plays.played_at', '>=', now()->subDays(14))
            ->where('track_artists.is_primary', true)
            ->select('artists.name', DB::raw('COUNT(*) as plays'))
            ->groupBy('artists.name')
            ->orderByDesc('plays')
            ->limit(8)
            ->get()
            ->map(fn ($r) => $r->name.' ('.$r->plays.' plays)')
            ->join(', ');

        return "## Music (last 14 days)\nTotal plays: {$total}\nTop artists: {$topArtists}";
    }

    private function mediaSummary(): ?string
    {
        $movies = MovieWatch::query()
            ->with('movie:id,title')
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays(14))
            ->get();

        $series = EpisodeWatch::query()
            ->join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->join('tv_seasons', 'tv_episodes.tv_season_id', '=', 'tv_seasons.id')
            ->join('tv_series', 'tv_seasons.tv_series_id', '=', 'tv_series.id')
            ->whereNotNull('episode_watches.watched_at')
            ->where('episode_watches.watched_at', '>=', now()->subDays(14))
            ->select('tv_series.name', DB::raw('COUNT(*) as episodes'))
            ->groupBy('tv_series.name')
            ->orderByDesc('episodes')
            ->get();

        if ($movies->isEmpty() && $series->isEmpty()) {
            return null;
        }

        $lines = ['## Movies & TV (last 14 days)'];

        if ($movies->isNotEmpty()) {
            $titles = $movies
                ->groupBy('movie_id')
                ->map(function ($g) {
                    $title = $g->first()->movie?->title ?? 'Unknown';
                    return $g->count() > 1 ? "{$title} ({$g->count()}×)" : $title;
                })
                ->values()
                ->join(', ');
            $lines[] = "Movies: {$titles}";
        }

        if ($series->isNotEmpty()) {
            $shows = $series->map(fn ($r) => $r->name.' ('.$r->episodes.' ep)')->join(', ');
            $lines[] = "TV: {$shows}";
        }

        return implode("\n", $lines);
    }
}
