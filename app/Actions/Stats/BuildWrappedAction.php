<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use App\Models\PlayStationTrophy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class BuildWrappedAction
{
    /** @return list<int> */
    public function availableYears(): array
    {
        $years = collect([
            HealthEntry::query()->whereNotNull('steps')->min('date'),
            PlayStationSession::query()->min('started_at'),
            Play::query()->whereNotNull('played_at')->min('played_at'),
            EpisodeWatch::query()->whereNotNull('watched_at')->min('watched_at'),
            MovieWatch::query()->whereNotNull('watched_at')->min('watched_at'),
        ])
            ->filter()
            ->map(fn ($date) => (int) Carbon::parse($date)->format('Y'))
            ->push(now()->year)
            ->unique()
            ->sort()
            ->values();

        $earliest = $years->first() ?? now()->year;

        return collect(range($earliest, now()->year))
            ->map(fn ($y) => (int) $y)
            ->values()
            ->all();
    }

    public function handle(int $year): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        return [
            'year'   => $year,
            'health' => $this->health($start, $end),
            'gaming' => $this->gaming($start, $end),
            'music'  => $this->music($start, $end),
            'movies' => $this->movies($start, $end),
            'tv'     => $this->tv($start, $end),
        ];
    }

    private function health(Carbon $start, Carbon $end): array
    {
        $entries = HealthEntry::query()
            ->whereNotNull('steps')
            ->whereBetween('date', [$start, $end])
            ->get(['steps', 'date']);

        $total = (int) $entries->sum('steps');
        $days  = $entries->count();
        $best  = $entries->sortByDesc('steps')->first();

        $bestMonth = $entries
            ->groupBy(fn ($e) => Carbon::parse($e->date)->format('Y-m'))
            ->map(fn ($g) => ['month' => $g->first()->date, 'total' => (int) $g->sum('steps')])
            ->sortByDesc('total')
            ->first();

        return [
            'total_steps'  => $total,
            'days_logged'  => $days,
            'avg_steps'    => $days > 0 ? (int) round($total / $days) : 0,
            'km_walked'    => $total > 0 ? (int) round($total * 0.00066) : 0,
            'best_day'     => $best ? [
                'steps' => (int) $best->steps,
                'date'  => Carbon::parse($best->date),
            ] : null,
            'best_month'   => $bestMonth ? [
                'label' => Carbon::parse($bestMonth['month'])->format('F'),
                'total' => $bestMonth['total'],
            ] : null,
        ];
    }

    private function gaming(Carbon $start, Carbon $end): array
    {
        $sessions = PlayStationSession::query()
            ->with('game:id,display_name,name,image_url')
            ->whereBetween('started_at', [$start, $end])
            ->get(['play_station_game_id', 'duration_minutes', 'started_at']);

        $totalMinutes = (int) $sessions->sum('duration_minutes');
        $sessionCount = $sessions->count();

        $topGames = $sessions
            ->groupBy('play_station_game_id')
            ->map(fn ($g) => [
                'id'        => $g->first()->game?->id,
                'name'      => $g->first()->game?->display_name ?? $g->first()->game?->name,
                'minutes'   => (int) $g->sum('duration_minutes'),
                'sessions'  => $g->count(),
                'image_url' => $g->first()->game?->image_url,
            ])
            ->sortByDesc('minutes')
            ->values()
            ->take(5)
            ->all();

        $gamesPlayed = $sessions->unique('play_station_game_id')->count();

        $longestSession = $sessions->sortByDesc('duration_minutes')->first();

        $bestMonth = $sessions
            ->groupBy(fn ($s) => Carbon::parse($s->started_at)->format('Y-m'))
            ->map(fn ($g) => ['month' => $g->first()->started_at, 'minutes' => (int) $g->sum('duration_minutes')])
            ->sortByDesc('minutes')
            ->first();

        $trophiesEarned = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereBetween('earned_at', [$start, $end])
            ->count();

        $trophiesByType = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereBetween('earned_at', [$start, $end])
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($v) => (int) $v)
            ->all();

        return [
            'total_minutes'    => $totalMinutes,
            'sessions'         => $sessionCount,
            'games_played'     => $gamesPlayed,
            'top_games'        => $topGames,
            'trophies_earned'  => $trophiesEarned,
            'trophies_by_type' => $trophiesByType,
            'longest_session'  => $longestSession ? (int) $longestSession->duration_minutes : null,
            'best_month'       => $bestMonth ? [
                'label'   => Carbon::parse($bestMonth['month'])->format('F'),
                'minutes' => $bestMonth['minutes'],
            ] : null,
        ];
    }

    private function music(Carbon $start, Carbon $end): array
    {
        $totalPlays = Play::query()
            ->whereNotNull('played_at')
            ->whereBetween('played_at', [$start, $end])
            ->count();

        $uniqueTracks = Play::query()
            ->whereNotNull('played_at')
            ->whereBetween('played_at', [$start, $end])
            ->distinct('track_id')
            ->count('track_id');

        $uniqueArtists = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->whereNotNull('plays.played_at')
            ->whereBetween('plays.played_at', [$start, $end])
            ->distinct('track_artists.artist_id')
            ->count('track_artists.artist_id');

        $minutesListened = (int) Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->whereNotNull('plays.played_at')
            ->whereBetween('plays.played_at', [$start, $end])
            ->whereNotNull('tracks.duration_ms')
            ->sum(DB::raw('tracks.duration_ms / 60000'));

        $topArtists = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->join('artists', 'track_artists.artist_id', '=', 'artists.id')
            ->whereNotNull('plays.played_at')
            ->whereBetween('plays.played_at', [$start, $end])
            ->select('artists.id', 'artists.name', 'artists.image_url', DB::raw('COUNT(*) as plays'))
            ->groupBy('artists.id', 'artists.name', 'artists.image_url')
            ->orderByDesc('plays')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'image_url' => $r->image_url, 'plays' => (int) $r->plays])
            ->all();

        $topTracks = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->join('artists', 'track_artists.artist_id', '=', 'artists.id')
            ->whereNotNull('plays.played_at')
            ->whereBetween('plays.played_at', [$start, $end])
            ->where('track_artists.is_primary', true)
            ->select('tracks.id', 'tracks.title', 'artists.name as artist', DB::raw('COUNT(*) as plays'))
            ->groupBy('tracks.id', 'tracks.title', 'artists.name')
            ->orderByDesc('plays')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'artist' => $r->artist, 'plays' => (int) $r->plays])
            ->all();

        $topAlbums = Play::query()
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('albums', 'tracks.album_id', '=', 'albums.id')
            ->whereNotNull('plays.played_at')
            ->whereBetween('plays.played_at', [$start, $end])
            ->whereNotNull('tracks.album_id')
            ->select('albums.id', 'albums.name', 'albums.image_url', DB::raw('COUNT(*) as plays'))
            ->groupBy('albums.id', 'albums.name', 'albums.image_url')
            ->orderByDesc('plays')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'image_url' => $r->image_url, 'plays' => (int) $r->plays])
            ->all();

        $bestMonth = Play::query()
            ->whereNotNull('played_at')
            ->whereBetween('played_at', [$start, $end])
            ->select(DB::raw('DATE_FORMAT(played_at, "%Y-%m") as month'), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->orderByDesc('total')
            ->first();

        return [
            'total_plays'      => $totalPlays,
            'unique_tracks'    => $uniqueTracks,
            'unique_artists'   => $uniqueArtists,
            'minutes_listened' => $minutesListened,
            'top_artists'      => $topArtists,
            'top_tracks'       => $topTracks,
            'top_albums'       => $topAlbums,
            'best_month'       => $bestMonth ? [
                'label' => Carbon::createFromFormat('Y-m', $bestMonth->month)->format('F'),
                'total' => (int) $bestMonth->total,
            ] : null,
        ];
    }

    private function movies(Carbon $start, Carbon $end): array
    {
        $watches = MovieWatch::query()
            ->with('movie:id,title,runtime,poster_path')
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$start, $end])
            ->get(['movie_id', 'watched_at']);

        $totalWatches  = $watches->count();
        $uniqueMovies  = $watches->unique('movie_id')->count();
        $totalMinutes  = (int) $watches->sum(fn ($w) => $w->movie?->runtime ?? 0);

        $topMovies = $watches
            ->groupBy('movie_id')
            ->map(fn ($g) => [
                'id'         => $g->first()->movie_id,
                'title'      => $g->first()->movie?->title,
                'count'      => $g->count(),
                'poster_url' => $g->first()->movie?->poster_url,
            ])
            ->sortByDesc('count')
            ->values()
            ->take(5)
            ->all();

        $bestMonth = $watches
            ->groupBy(fn ($w) => Carbon::parse($w->watched_at)->format('Y-m'))
            ->map(fn ($g) => ['month' => $g->first()->watched_at, 'count' => $g->count()])
            ->sortByDesc('count')
            ->first();

        return [
            'total_watches'         => $totalWatches,
            'unique_movies'         => $uniqueMovies,
            'total_runtime_minutes' => $totalMinutes,
            'top_movies'            => $topMovies,
            'best_month'            => $bestMonth ? [
                'label' => Carbon::parse($bestMonth['month'])->format('F'),
                'count' => $bestMonth['count'],
            ] : null,
        ];
    }

    private function tv(Carbon $start, Carbon $end): array
    {
        $totalEpisodes = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$start, $end])
            ->count();

        $totalMinutes = (int) EpisodeWatch::query()
            ->join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->whereNotNull('episode_watches.watched_at')
            ->whereBetween('episode_watches.watched_at', [$start, $end])
            ->whereNotNull('tv_episodes.runtime')
            ->sum('tv_episodes.runtime');

        $topSeries = EpisodeWatch::query()
            ->join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->join('tv_seasons', 'tv_episodes.tv_season_id', '=', 'tv_seasons.id')
            ->join('tv_series', 'tv_seasons.tv_series_id', '=', 'tv_series.id')
            ->whereNotNull('episode_watches.watched_at')
            ->whereBetween('episode_watches.watched_at', [$start, $end])
            ->select('tv_series.id', 'tv_series.name', 'tv_series.poster_path', DB::raw('COUNT(*) as episodes'))
            ->groupBy('tv_series.id', 'tv_series.name', 'tv_series.poster_path')
            ->orderByDesc('episodes')
            ->limit(5)
            ->get();

        $tmdbBase = config('tmdb.image_base_url') . config('tmdb.poster_sizes.medium');

        $topSeriesMapped = $topSeries
            ->map(fn ($r) => [
                'id'         => $r->id,
                'name'       => $r->name,
                'episodes'   => (int) $r->episodes,
                'poster_url' => $r->poster_path ? $tmdbBase . $r->poster_path : null,
            ])
            ->all();

        $bestMonth = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$start, $end])
            ->select(DB::raw('DATE_FORMAT(watched_at, "%Y-%m") as month'), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->orderByDesc('total')
            ->first();

        return [
            'total_episodes'        => $totalEpisodes,
            'total_runtime_minutes' => $totalMinutes,
            'top_series'            => $topSeriesMapped,
            'best_month'            => $bestMonth ? [
                'label' => Carbon::createFromFormat('Y-m', $bestMonth->month)->format('F'),
                'total' => (int) $bestMonth->total,
            ] : null,
        ];
    }
}
