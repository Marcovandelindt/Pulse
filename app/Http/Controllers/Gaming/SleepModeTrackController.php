<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\SleepModeTrack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class SleepModeTrackController extends Controller
{
    public function index(): View
    {
        $tracks = SleepModeTrack::orderBy('sort_order')->orderBy('id')->get();

        return view('pages.playstation.sleep-music', compact('tracks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'audio'     => ['required', 'file', 'mimes:mp3,ogg,wav,m4a,flac', 'max:51200'],
            'title'     => ['nullable', 'string', 'max:255'],
            'game_name' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('audio')->store('sleep-mode/audio', 'public');

        SleepModeTrack::create([
            'path'       => $path,
            'title'      => $request->input('title') ?: null,
            'game_name'  => $request->input('game_name') ?: null,
            'sort_order' => SleepModeTrack::max('sort_order') + 1,
        ]);

        return redirect()->route('playstation.sleep-music.index')
            ->with('success', 'Track added.');
    }

    public function destroy(SleepModeTrack $sleepModeTrack): RedirectResponse
    {
        Storage::disk('public')->delete($sleepModeTrack->path);
        $sleepModeTrack->delete();

        return redirect()->route('playstation.sleep-music.index')
            ->with('success', 'Track removed.');
    }
}
