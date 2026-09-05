<?php

declare(strict_types=1);

namespace App\Services\Ollama;

use App\Models\AiMemory;
use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ContextBuilderService
{
    public function buildSystemPrompt(): string
    {
        $sections = [
            $this->introduction(),
            $this->memorySummary(),
            $this->healthSummary(),
            $this->gamingSummary(),
            $this->musicSummary(),
            $this->mediaSummary(),
        ];

        return implode("\n\n", array_filter($sections));
    }

    private function introduction(): string
    {
        $lines = [
            'You are Marco\'s personal data assistant inside his Pulse dashboard.',
            'You have access to his recent activity across health, gaming, music and media.',
            'Answer in the same language the user writes in (Dutch or English).',
            'Today is '.now()->format('l, j F Y').'.',
            '',
            'STRICT RULES — follow these without exception:',
            '1. Only use facts that are explicitly present in the data below. Never invent, assume or extrapolate details.',
            '2. If specific information (e.g. a last-watched episode) is not in the data, say exactly that: "I don\'t have that information."',
            '3. Never mention series, games, tracks or episodes that are not listed in the data below.',
            '4. If you are uncertain, say so. Do not guess.',
        ];

        $personality = Setting::getAiPersonality();
        if ($personality) {
            $lines[] = '';
            $lines[] = 'Additional instructions from Marco:';
            $lines[] = $personality;
        }

        return implode("\n", $lines);
    }

    private function memorySummary(): ?string
    {
        $memories = AiMemory::orderBy('created_at')->pluck('content');

        if ($memories->isEmpty()) {
            return null;
        }

        $lines = $memories->map(fn ($m) => "- {$m}")->join("\n");

        return "## What I know about Marco (learned from past conversations)\n{$lines}";
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
            ->orderByDesc('started_at')
            ->get(['play_station_game_id', 'duration_minutes', 'started_at']);

        if ($sessions->isEmpty()) {
            return null;
        }

        $total = (int) $sessions->sum('duration_minutes');

        $byGame = $sessions
            ->groupBy('play_station_game_id')
            ->map(function ($g) {
                $name     = $g->first()->game?->display_name ?? $g->first()->game?->name ?? 'Unknown';
                $minutes  = (int) $g->sum('duration_minutes');
                $sessions = $g->count();
                return "{$name}: {$minutes} min ({$sessions} sessions)";
            })
            ->sortByDesc(fn ($v) => (int) explode(' ', $v)[1])
            ->values()
            ->join(', ');

        $last       = $sessions->first();
        $lastName   = $last->game?->display_name ?? $last->game?->name ?? 'Unknown';
        $lastDate   = Carbon::parse($last->started_at)->format('D d M H:i');
        $lastFact   = "Last gaming session: {$lastName} on {$lastDate} ({$last->duration_minutes} min)";

        $recentSessions = $sessions->take(10)->map(function ($s) {
            $name = $s->game?->display_name ?? $s->game?->name ?? 'Unknown';
            $date = Carbon::parse($s->started_at)->format('D d M');
            return "- {$date}: {$name} – {$s->duration_minutes} min";
        })->join("\n");

        return "## Gaming (last 14 days)\n{$lastFact}\nGames: {$byGame}\nTotal: {$total} min across {$sessions->count()} sessions\nRecent sessions (newest first):\n{$recentSessions}";
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

        $topTracks = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->join('artists', 'track_artists.artist_id', '=', 'artists.id')
            ->whereNotNull('plays.played_at')
            ->where('plays.played_at', '>=', now()->subDays(14))
            ->where('track_artists.is_primary', true)
            ->select('tracks.title', 'artists.name as artist', DB::raw('COUNT(*) as plays'))
            ->groupBy('tracks.title', 'artists.name')
            ->orderByDesc('plays')
            ->limit(8)
            ->get()
            ->map(fn ($r) => "\"{$r->title}\" by {$r->artist} ({$r->plays} plays)")
            ->join(', ');

        $recentPlays = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->join('artists', 'track_artists.artist_id', '=', 'artists.id')
            ->whereNotNull('plays.played_at')
            ->where('plays.played_at', '>=', now()->subDays(14))
            ->where('track_artists.is_primary', true)
            ->select('tracks.title', 'artists.name as artist', 'plays.played_at')
            ->orderByDesc('plays.played_at')
            ->limit(15)
            ->get()
            ->map(fn ($r) => '- '.Carbon::parse($r->played_at)->format('D d M H:i').": \"{$r->title}\" – {$r->artist}")
            ->join("\n");

        $lastPlay = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->join('artists', 'track_artists.artist_id', '=', 'artists.id')
            ->whereNotNull('plays.played_at')
            ->where('track_artists.is_primary', true)
            ->select('tracks.title', 'artists.name as artist', 'plays.played_at')
            ->orderByDesc('plays.played_at')
            ->first();

        $lastPlayFact = $lastPlay
            ? 'Last played track: "'.$lastPlay->title.'" by '.$lastPlay->artist.' on '.Carbon::parse($lastPlay->played_at)->format('D d M H:i')
            : null;

        return implode("\n", array_filter([
            '## Music (last 14 days)',
            $lastPlayFact,
            "Total plays: {$total}",
            "Top artists: {$topArtists}",
            "Top tracks: {$topTracks}",
            "Recently played (newest first):",
            $recentPlays,
        ]));
    }

    private function mediaSummary(): ?string
    {
        $movies = MovieWatch::query()
            ->with('movie:id,title')
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays(14))
            ->get();

        $recentEpisodes = EpisodeWatch::query()
            ->join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->join('tv_seasons', 'tv_episodes.tv_season_id', '=', 'tv_seasons.id')
            ->join('tv_series', 'tv_seasons.tv_series_id', '=', 'tv_series.id')
            ->whereNotNull('episode_watches.watched_at')
            ->where('episode_watches.watched_at', '>=', now()->subDays(14))
            ->select(
                'tv_series.name as series_name',
                'tv_seasons.season_number',
                'tv_episodes.episode_number',
                'tv_episodes.name as episode_name',
                'episode_watches.watched_at',
            )
            ->orderByDesc('episode_watches.watched_at')
            ->limit(20)
            ->get();

        $series = $recentEpisodes
            ->groupBy('series_name')
            ->map(fn ($g) => [
                'name'         => $g->first()->series_name,
                'episodes'     => $g->count(),
                'last_watched' => $g->first()->watched_at,
            ])
            ->sortByDesc('last_watched')
            ->values();

        if ($movies->isEmpty() && $recentEpisodes->isEmpty()) {
            return null;
        }

        $lines = ['## Movies & TV (last 14 days)'];

        if ($recentEpisodes->isNotEmpty()) {
            $first = $recentEpisodes->first();
            $code  = sprintf('S%02dE%02d', $first->season_number, $first->episode_number);
            $lines[] = "Last watched episode: {$first->series_name} {$code} – {$first->episode_name} on ".Carbon::parse($first->watched_at)->format('D d M H:i');
        }

        if ($movies->isNotEmpty()) {
            $titles = $movies
                ->groupBy('movie_id')
                ->map(function ($g) {
                    $title = $g->first()->movie?->title ?? 'Unknown';
                    return $g->count() > 1 ? "{$title} ({$g->count()}×)" : $title;
                })
                ->values()
                ->join(', ');
            $lines[] = "Movies watched: {$titles}";
        }

        if ($series->isNotEmpty()) {
            $shows = $series->map(fn ($r) => $r['name'].' ('.$r['episodes'].' ep, last watched '.Carbon::parse($r['last_watched'])->format('D d M').')')->join(', ');
            $lines[] = "TV series (most recently watched first): {$shows}";
        }

        if ($recentEpisodes->isNotEmpty()) {
            $lines[] = "Recent episodes watched (newest first):";
            foreach ($recentEpisodes as $ep) {
                $code = sprintf('S%02dE%02d', $ep->season_number, $ep->episode_number);
                $lines[] = '- '.Carbon::parse($ep->watched_at)->format('D d M').': '.$ep->series_name.' '.$code.' – '.$ep->episode_name;
            }
        }

        return implode("\n", $lines);
    }
}
