<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\SteamGame;
use App\Models\SteamGameImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class SteamGameImageController extends Controller
{
    public function store(Request $request, SteamGame $game): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:51200'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store('steam/gallery', 'r2');

        $game->images()->create([
            'path' => $path,
            'disk' => 'r2',
            'caption' => $request->input('caption'),
            'sort_order' => $game->images()->max('sort_order') + 1,
        ]);

        return redirect()->route('steam.games.show', $game)
            ->with('success', 'Image added to gallery.');
    }

    public function destroy(SteamGame $game, SteamGameImage $steamGameImage): RedirectResponse
    {
        Storage::disk($steamGameImage->disk ?? 'public')->delete($steamGameImage->path);
        $steamGameImage->delete();

        return redirect()->route('steam.games.show', $game)
            ->with('success', 'Image removed from gallery.');
    }
}
