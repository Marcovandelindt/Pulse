<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Play;
use App\Models\SpotifySyncCursor;
use App\Services\Spotify\SpotifyTrackService;
use Illuminate\Contracts\View\View;

final class MusicDashboardController extends Controller
{
    public function __construct(
        private readonly SpotifyTrackService $trackService,
    ) {}

    public function index(): View
    {
        $recentPlays = Play::with(['track.album', 'track.artists'])
            ->orderByDesc('played_at')
            ->limit(20)
            ->get();

        $topTracksThisWeek = Play::selectRaw('track_id, count(*) as play_count')
            ->where('played_at', '>=', now()->subWeek())
            ->groupBy('track_id')
            ->orderByDesc('play_count')
            ->limit(5)
            ->with(['track.artists', 'track.album'])
            ->get();

        $topArtistsThisWeek = Artist::select('artists.*')
            ->selectRaw('COUNT(plays.id) as play_count')
            ->join('track_artists', 'artists.id', '=', 'track_artists.artist_id')
            ->join('tracks', 'track_artists.track_id', '=', 'tracks.id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->where('plays.played_at', '>=', now()->subWeek())
            ->where('track_artists.is_primary', true)
            ->groupBy('artists.id', 'artists.spotify_artist_id', 'artists.name', 'artists.image_url', 'artists.genres', 'artists.popularity', 'artists.created_at', 'artists.updated_at')
            ->orderByDesc('play_count')
            ->limit(5)
            ->get();

        $currentlyPlaying = $this->trackService->getCurrentlyPlaying();
        $lastSync = SpotifySyncCursor::latest()->first();

        return view('pages.music.index', compact(
            'recentPlays',
            'topTracksThisWeek',
            'topArtistsThisWeek',
            'currentlyPlaying',
            'lastSync',
        ));
    }
}
