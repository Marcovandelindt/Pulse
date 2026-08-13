<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Track;
use Illuminate\Contracts\View\View;

final class TrackController extends Controller
{
    public function show(Track $track): View
    {
        $track->load(['album', 'artists', 'plays']);

        $playCount = $track->plays->count();
        $firstPlay = $track->plays->sortBy('played_at')->first();
        $lastPlay = $track->plays->sortByDesc('played_at')->first();

        return view('pages.music.tracks.show', compact('track', 'playCount', 'firstPlay', 'lastPlay'));
    }
}
