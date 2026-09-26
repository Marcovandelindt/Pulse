<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\NintendoGame;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class NintendoCoverController extends Controller
{
    public function store(Request $request, NintendoGame $game): RedirectResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($game->image_url && str_starts_with($game->image_url, '/storage/')) {
            Storage::disk('public')->delete(
                ltrim(str_replace('/storage/', '', $game->image_url), '/')
            );
        }

        $path = $request->file('cover')->store('nintendo-covers', 'public');

        $game->update(['image_url' => '/storage/' . $path]);

        return redirect()->route('nintendo.show', $game)
            ->with('success', 'Cover updated.');
    }
}
