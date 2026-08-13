<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Play;
use App\Models\Track;
use Illuminate\Contracts\View\View;

final class MusicStatsController extends Controller
{
    public function index(): View
    {
        $totalPlays = Play::count();
        $totalMinutes = (int) (Play::join('tracks', 'plays.track_id', '=', 'tracks.id')->sum('tracks.duration_ms') / 60000);
        $uniqueTracks = Track::has('plays')->count();
        $uniqueArtists = Artist::whereHas('tracks', fn ($q) => $q->has('plays'))->count();
        $uniqueAlbums = Album::whereHas('tracks', fn ($q) => $q->has('plays'))->count();
        $firstPlay = Play::with(['track.artists'])->oldest('played_at')->first();
        $lastPlay = Play::with(['track.artists'])->latest('played_at')->first();

        $topTracks = Play::selectRaw('track_id, count(*) as play_count')
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
            ->where('track_artists.is_primary', true)
            ->groupBy('artists.id', 'artists.spotify_artist_id', 'artists.name', 'artists.image_url', 'artists.genres', 'artists.popularity', 'artists.created_at', 'artists.updated_at')
            ->orderByDesc('play_count')
            ->limit(10)
            ->get();

        $topAlbums = Album::select('albums.*')
            ->selectRaw('COUNT(plays.id) as play_count')
            ->join('tracks', 'albums.id', '=', 'tracks.album_id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->groupBy('albums.id', 'albums.spotify_album_id', 'albums.name', 'albums.image_url', 'albums.release_date', 'albums.album_type', 'albums.total_tracks', 'albums.created_at', 'albums.updated_at')
            ->orderByDesc('play_count')
            ->limit(10)
            ->get();

        $playsPerDay = Play::selectRaw('DATE(played_at) as date, count(*) as count')
            ->where('played_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $avgPlaysPerDay = $totalPlays > 0
            ? round($totalPlays / max(1, (int) now()->diffInDays(optional($firstPlay)->played_at ?? now()) + 1), 1)
            : 0;

        return view('pages.music.stats', compact(
            'totalPlays',
            'totalMinutes',
            'uniqueTracks',
            'uniqueArtists',
            'uniqueAlbums',
            'firstPlay',
            'lastPlay',
            'topTracks',
            'topArtists',
            'topAlbums',
            'playsPerDay',
            'avgPlaysPerDay',
        ));
    }
}
