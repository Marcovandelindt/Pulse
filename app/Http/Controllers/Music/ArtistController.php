<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Artist;
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

        return view('pages.music.artists.show', compact('artist', 'totalPlays', 'uniqueTracks', 'topTracks', 'albums'));
    }
}
