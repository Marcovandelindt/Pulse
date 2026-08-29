<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Play;
use App\Models\Track;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class MusicStatsController extends Controller
{
    public function index(Request $request): View
    {
        $availableYears = Play::selectRaw('YEAR(played_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y);

        $selectedYear = $request->query('year', (string) now()->year);
        $isAllYears   = $selectedYear === 'all' || ! $availableYears->contains((int) $selectedYear);

        if (! $isAllYears) {
            $selectedYear = (int) $selectedYear;
        }

        $baseQuery = fn () => $isAllYears
            ? Play::query()
            : Play::whereYear('played_at', $selectedYear);

        $totalPlays = $baseQuery()->count();

        $totalMinutes = (int) (
            $baseQuery()
                ->join('tracks', 'plays.track_id', '=', 'tracks.id')
                ->sum('tracks.duration_ms') / 60000
        );

        $uniqueTracks = Track::whereHas(
            'plays', fn ($q) => $isAllYears ? $q : $q->whereYear('played_at', $selectedYear)
        )->count();

        $uniqueArtists = Artist::whereHas(
            'tracks', fn ($q) => $q->whereHas('plays', fn ($q) => $isAllYears ? $q : $q->whereYear('played_at', $selectedYear))
        )->count();

        $uniqueAlbums = Album::whereHas(
            'tracks', fn ($q) => $q->whereHas('plays', fn ($q) => $isAllYears ? $q : $q->whereYear('played_at', $selectedYear))
        )->count();

        if ($isAllYears) {
            $firstPlay  = Play::min('played_at');
            $daysSoFar  = $firstPlay ? (int) Carbon::parse($firstPlay)->diffInDays(now()) + 1 : 1;
        } elseif ($selectedYear === now()->year) {
            $daysSoFar = (int) now()->startOfYear()->diffInDays(now()) + 1;
        } else {
            $daysSoFar = Carbon::create($selectedYear)->isLeapYear() ? 366 : 365;
        }

        $avgPlaysPerDay = $totalPlays > 0 ? round($totalPlays / $daysSoFar, 1) : 0;

        $topTracks = $baseQuery()
            ->selectRaw('track_id, count(*) as play_count')
            ->groupBy('track_id')
            ->orderByDesc('play_count')
            ->limit(10)
            ->with(['track.album', 'track.artists'])
            ->get();

        $topArtists = Artist::select('artists.*')
            ->selectRaw('COUNT(plays.id) as play_count')
            ->join('track_artists', 'artists.id', '=', 'track_artists.artist_id')
            ->join('tracks', 'track_artists.track_id', '=', 'tracks.id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->when(! $isAllYears, fn ($q) => $q->whereYear('plays.played_at', $selectedYear))
            ->where('track_artists.is_primary', true)
            ->groupBy('artists.id', 'artists.spotify_artist_id', 'artists.name', 'artists.image_url', 'artists.genres', 'artists.popularity', 'artists.created_at', 'artists.updated_at')
            ->orderByDesc('play_count')
            ->limit(10)
            ->get();

        $topAlbums = Album::select('albums.*')
            ->selectRaw('COUNT(plays.id) as play_count')
            ->join('tracks', 'albums.id', '=', 'tracks.album_id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->when(! $isAllYears, fn ($q) => $q->whereYear('plays.played_at', $selectedYear))
            ->groupBy('albums.id', 'albums.spotify_album_id', 'albums.name', 'albums.image_url', 'albums.release_date', 'albums.album_type', 'albums.total_tracks', 'albums.created_at', 'albums.updated_at')
            ->orderByDesc('play_count')
            ->limit(10)
            ->get();

        if ($isAllYears) {
            $rawChart  = Play::selectRaw('YEAR(played_at) as period, count(*) as count')
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('count', 'period');
            $chartData = $availableYears->sortBy(fn ($y) => $y)->map(fn ($y) => [
                'label' => (string) $y,
                'count' => $rawChart->get($y, 0),
            ])->values();
            $chartTitle = 'Plays per year';
        } else {
            $maxMonth  = $selectedYear === now()->year ? now()->month : 12;
            $rawChart  = Play::selectRaw('MONTH(played_at) as period, count(*) as count')
                ->whereYear('played_at', $selectedYear)
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('count', 'period');
            $chartData = collect(range(1, $maxMonth))->map(fn ($m) => [
                'label' => Carbon::create($selectedYear, $m)->format('M'),
                'count' => $rawChart->get($m, 0),
            ]);
            $chartTitle = "Plays per month — {$selectedYear}";
        }

        return view('pages.music.stats', compact(
            'selectedYear',
            'isAllYears',
            'availableYears',
            'totalPlays',
            'totalMinutes',
            'uniqueTracks',
            'uniqueArtists',
            'uniqueAlbums',
            'avgPlaysPerDay',
            'topTracks',
            'topArtists',
            'topAlbums',
            'chartData',
            'chartTitle',
        ));
    }
}
