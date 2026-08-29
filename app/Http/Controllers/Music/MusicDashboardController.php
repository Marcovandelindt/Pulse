<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Play;
use App\Models\PlayStationGame;
use App\Models\SpotifySyncCursor;
use App\Models\SteamGame;
use App\Models\Track;
use App\Services\Spotify\SpotifyTrackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class MusicDashboardController extends Controller
{
    public function __construct(
        private readonly SpotifyTrackService $trackService,
    ) {}

    /** @param \Illuminate\Support\Collection<int, Track> $tracks */
    private function buildGameMap(\Illuminate\Support\Collection $tracks): array
    {
        $map = [];

        $psIds    = $tracks->where('gameable_type', 'playstation')->pluck('gameable_id')->filter()->unique()->values();
        $steamIds = $tracks->where('gameable_type', 'steam')->pluck('gameable_id')->filter()->unique()->values();

        if ($psIds->isNotEmpty()) {
            foreach (PlayStationGame::whereIn('id', $psIds)->get(['id', 'name', 'display_name', 'platform', 'image_url']) as $g) {
                $map['playstation:'.$g->id] = $g;
            }
        }

        if ($steamIds->isNotEmpty()) {
            foreach (SteamGame::whereIn('id', $steamIds)->get(['id', 'name', 'image_url', 'custom_image_url']) as $g) {
                $map['steam:'.$g->id] = $g;
            }
        }

        return $map;
    }

    public function sync(): RedirectResponse
    {
        $result = $this->trackService->syncRecentlyPlayed();

        return redirect()->route('music.index')
            ->with('success', "Synced {$result['synced']} new plays ({$result['skipped']} skipped).");
    }

    public function index(): View
    {
        $recentPlays = Play::with(['track.album', 'track.artists'])
            ->orderByDesc('played_at')
            ->paginate(20);

        $topTracksThisWeek = Play::selectRaw('track_id, count(*) as play_count')
            ->whereBetween('played_at', [now()->startOfWeek(), now()->endOfWeek()])
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
            ->whereBetween('plays.played_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('track_artists.is_primary', true)
            ->groupBy('artists.id', 'artists.spotify_artist_id', 'artists.name', 'artists.image_url', 'artists.genres', 'artists.popularity', 'artists.created_at', 'artists.updated_at')
            ->orderByDesc('play_count')
            ->limit(5)
            ->get();

        $currentlyPlaying = $this->trackService->getCurrentlyPlaying();

        if ($currentlyPlaying !== null) {
            $track = Track::where('spotify_track_id', $currentlyPlaying['spotify_track_id'])->first();

            if ($track === null) {
                $track = $this->trackService->upsertTrack($currentlyPlaying['raw_track'], fetchDetails: false);
            }

            $currentlyPlaying['track']         = $track->load('artists');
            $currentlyPlaying['artist_models'] = $track->artists->keyBy('spotify_artist_id');
        }
        $lastSync = SpotifySyncCursor::latest()->first();

        $obsessions = Track::where('is_obsession', true)
            ->whereNotNull('obsession_since')
            ->with(['album', 'artists'])
            ->get()
            ->each(function (Track $track) {
                $track->plays_since_obsession = Play::where('track_id', $track->id)
                    ->where('played_at', '>=', $track->obsession_since)
                    ->count();
            });

        $obsessionSlides = $obsessions
            ->filter(fn (Track $t) => $t->album?->image_url)
            ->map(fn (Track $t) => [
                'name' => $t->title,
                'artist' => $t->artists_string,
                'image' => $t->album->image_url,
                'plays' => $t->plays_since_obsession,
            ])
            ->values();

        $gameMap = $this->buildGameMap(
            $recentPlays->pluck('track')
                ->merge($topTracksThisWeek->pluck('track'))
                ->merge($obsessions)
                ->filter()
        );

        return view('pages.music.index', compact(
            'recentPlays',
            'topTracksThisWeek',
            'topArtistsThisWeek',
            'currentlyPlaying',
            'lastSync',
            'obsessions',
            'obsessionSlides',
            'gameMap',
        ));
    }
}
