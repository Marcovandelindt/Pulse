<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use App\Models\PlayStationGameImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class PlayStationGameImageController extends Controller
{
    public function store(Request $request, PlayStationGame $playStationGame): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:51200'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store('playstation/gallery', 'public');

        $playStationGame->images()->create([
            'path' => $path,
            'caption' => $request->input('caption'),
            'sort_order' => $playStationGame->images()->max('sort_order') + 1,
        ]);

        return redirect()->route('playstation.show', $playStationGame)
            ->with('success', 'Image added to gallery.');
    }

    public function destroy(PlayStationGame $playStationGame, PlayStationGameImage $playStationGameImage): RedirectResponse
    {
        Storage::disk('public')->delete($playStationGameImage->path);
        $playStationGameImage->delete();

        return redirect()->route('playstation.show', $playStationGame)
            ->with('success', 'Image removed from gallery.');
    }
}
