<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Contracts\View\View;

final class AlbumController extends Controller
{
    public function show(Album $album): View
    {
        $album->load(['tracks.artists', 'tracks.plays']);

        $totalPlays = $album->tracks->sum(fn ($track) => $track->plays->count());

        return view('pages.music.albums.show', compact('album', 'totalPlays'));
    }
}
