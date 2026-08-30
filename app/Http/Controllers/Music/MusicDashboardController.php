<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Queries\Music\MusicDashboardQuery;
use App\Services\Spotify\SpotifyTrackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class MusicDashboardController extends Controller
{
    public function __construct(
        private readonly SpotifyTrackService $trackService,
    ) {}

    public function sync(): RedirectResponse
    {
        $result = $this->trackService->syncRecentlyPlayed();

        return redirect()->route('music.index')
            ->with('success', "Synced {$result['synced']} new plays ({$result['skipped']} skipped).");
    }

    public function index(MusicDashboardQuery $query): View
    {
        return view('pages.music.index', $query->handle());
    }
}
