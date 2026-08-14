<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Play;
use Illuminate\Contracts\View\View;

final class ArtistController extends Controller
{
    public function show(Artist $artist): View
    {
        $artist->load(['tracks.album', 'tracks.plays']);

        $totalPlays = $artist->tracks->sum(fn ($track) => $track->plays->count());
        $uniqueTracks = $artist->tracks->count();
        $topTracks = $artist->tracks->sortByDesc(fn ($track) => $track->plays->count())->take(10);
        $albums = $artist->tracks->pluck('album')->unique('id')->values();

        $totalListeningMs = $artist->tracks->sum(
            fn ($track) => $track->plays->count() * ($track->duration_ms ?? 0)
        );
        $listeningHours = (int) ($totalListeningMs / 3_600_000);
        $listeningMinutes = (int) (($totalListeningMs % 3_600_000) / 60_000);
        $totalListeningFormatted = $listeningHours > 0
            ? "{$listeningHours}h {$listeningMinutes}m"
            : "{$listeningMinutes}m";

        $recentPlays = Play::query()
            ->whereIn('track_id', $artist->tracks->pluck('id'))
            ->with(['track.album'])
            ->orderByDesc('played_at')
            ->paginate(25);

        return view('pages.music.artists.show', compact('artist', 'totalPlays', 'uniqueTracks', 'topTracks', 'albums', 'totalListeningFormatted', 'recentPlays'));
    }
}
