<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Actions\Music\CreateSpotifyPlaylist;
use App\Http\Controllers\Controller;
use App\Http\Requests\Music\CreateSpotifyPlaylistRequest;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class SpotifyPlaylistController extends Controller
{
    public function __construct(
        private readonly CreateSpotifyPlaylist $action,
    ) {}

    public function store(CreateSpotifyPlaylistRequest $request): RedirectResponse
    {
        try {
            $result = $this->action->handle(
                periodType:   $request->validated('period_type'),
                trackLimit:   (int) $request->validated('track_limit'),
                playlistName: $request->validated('playlist_name'),
                year:         $request->validated('year') !== null ? (int) $request->validated('year') : null,
                from:         $request->validated('from'),
                to:           $request->validated('to'),
            );

            return redirect()
                ->route('music.index')
                ->with('success', "Playlist \"{$result['name']}\" created with {$result['track_count']} tracks.")
                ->with('playlist_url', $result['url']);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
