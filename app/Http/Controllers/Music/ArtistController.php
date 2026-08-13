<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\View\View;

final class ArtistController extends Controller
{
    public function show(Artist $artist): View
    {
        $albums = $artist->albums()->with('listens')->get();

        $totalListens = $albums->sum(fn (object $a) => $a->listens->count());
        $averageRating = $albums->flatMap->listens->whereNotNull('rating')->avg('rating');

        $listenedAlbums = $albums->filter(fn (object $a) => $a->first_listened_at !== null);
        $firstListened = $listenedAlbums->sortBy('first_listened_at')->first()?->first_listened_at;
        $lastListened = $listenedAlbums->sortByDesc('last_listened_at')->first()?->last_listened_at;

        return view('pages.music.artists.show', compact(
            'artist', 'albums', 'totalListens', 'averageRating', 'firstListened', 'lastListened',
        ));
    }
}
