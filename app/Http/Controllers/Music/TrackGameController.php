<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use App\Models\SteamGame;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TrackGameController extends Controller
{
    public function update(Request $request, Track $track): RedirectResponse
    {
        $validated = $request->validate([
            'gameable_type' => ['required', 'in:playstation,steam'],
            'gameable_id'   => ['required', 'integer', 'min:1'],
        ]);

        $game = match ($validated['gameable_type']) {
            'playstation' => PlayStationGame::findOrFail($validated['gameable_id']),
            'steam'       => SteamGame::findOrFail($validated['gameable_id']),
        };

        $track->game()->associate($game);
        $track->save();

        return redirect()->route('music.tracks.show', $track)->with('success', 'Game linked.');
    }

    public function destroy(Track $track): RedirectResponse
    {
        $track->game()->dissociate();
        $track->save();

        return redirect()->route('music.tracks.show', $track)->with('success', 'Game unlinked.');
    }
}
