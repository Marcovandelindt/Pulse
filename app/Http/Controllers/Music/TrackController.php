<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use App\Models\SteamGame;
use App\Models\Track;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TrackController extends Controller
{
    public function show(Track $track): View
    {
        $track->load(['album', 'artists', 'plays', 'game']);

        $playCount = $track->plays->count();
        $firstPlay = $track->plays->sortBy('played_at')->first();
        $lastPlay  = $track->plays->sortByDesc('played_at')->first();
        $heroImage = $track->primaryArtist?->image_url ?? $track->album?->image_url;

        $playstationGames = PlayStationGame::orderBy('name')
            ->get(['id', 'name', 'display_name', 'platform']);

        $steamGames = SteamGame::orderBy('name')->get(['id', 'name']);

        return view('pages.music.tracks.show', compact(
            'track', 'playCount', 'firstPlay', 'lastPlay', 'heroImage',
            'playstationGames', 'steamGames',
        ));
    }

    public function update(Request $request, Track $track): JsonResponse
    {
        $validated = $request->validate([
            'genres'   => ['nullable', 'array'],
            'genres.*' => ['string', 'max:50'],
        ]);

        $track->update(['genres' => $validated['genres'] ?? []]);

        return response()->json(['genres' => $track->fresh()->genres ?? []]);
    }
}
