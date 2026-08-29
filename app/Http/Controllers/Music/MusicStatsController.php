<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Play;
use App\Models\Track;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

final class MusicStatsController extends Controller
{
    public function index(): View
    {
        $year      = now()->year;
        $yearStart = now()->startOfYear();
        $yearEnd   = now()->endOfYear();

        $totalPlays = Play::whereYear('played_at', $year)->count();

        $totalMinutes = (int) (
            Play::whereYear('played_at', $year)
                ->join('tracks', 'plays.track_id', '=', 'tracks.id')
                ->sum('tracks.duration_ms') / 60000
        );

        $uniqueTracks = Track::whereHas(
            'plays', fn ($q) => $q->whereYear('played_at', $year)
        )->count();

        $uniqueArtists = Artist::whereHas(
            'tracks', fn ($q) => $q->whereHas('plays', fn ($q) => $q->whereYear('played_at', $year))
        )->count();

        $uniqueAlbums = Album::whereHas(
            'tracks', fn ($q) => $q->whereHas('plays', fn ($q) => $q->whereYear('played_at', $year))
        )->count();

        $daysSoFar      = (int) $yearStart->diffInDays(now()) + 1;
        $avgPlaysPerDay = $totalPlays > 0 ? round($totalPlays / $daysSoFar, 1) : 0;

        $topTracks = Play::selectRaw('track_id, count(*) as play_count')
            ->whereYear('played_at', $year)
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
            ->whereYear('plays.played_at', $year)
            ->where('track_artists.is_primary', true)
            ->groupBy('artists.id', 'artists.spotify_artist_id', 'artists.name', 'artists.image_url', 'artists.genres', 'artists.popularity', 'artists.created_at', 'artists.updated_at')
            ->orderByDesc('play_count')
            ->limit(10)
            ->get();

        $topAlbums = Album::select('albums.*')
            ->selectRaw('COUNT(plays.id) as play_count')
            ->join('tracks', 'albums.id', '=', 'tracks.album_id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->whereYear('plays.played_at', $year)
            ->groupBy('albums.id', 'albums.spotify_album_id', 'albums.name', 'albums.image_url', 'albums.release_date', 'albums.album_type', 'albums.total_tracks', 'albums.created_at', 'albums.updated_at')
            ->orderByDesc('play_count')
            ->limit(10)
            ->get();

        $rawMonthly = Play::selectRaw('MONTH(played_at) as month, count(*) as count')
            ->whereYear('played_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $playsPerMonth = collect(range(1, now()->month))->map(fn ($m) => [
            'label' => Carbon::create($year, $m)->format('M'),
            'count' => $rawMonthly->get($m, 0),
        ]);

        return view('pages.music.stats', compact(
            'year',
            'totalPlays',
            'totalMinutes',
            'uniqueTracks',
            'uniqueArtists',
            'uniqueAlbums',
            'avgPlaysPerDay',
            'topTracks',
            'topArtists',
            'topAlbums',
            'playsPerMonth',
        ));
    }
}
