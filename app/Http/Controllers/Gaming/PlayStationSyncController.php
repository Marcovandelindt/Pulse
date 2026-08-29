<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\SyncPlayStationGames;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class PlayStationSyncController extends Controller
{
    public function store(SyncPlayStationGames $action): RedirectResponse
    {
        try {
            $synced = $action->handle();

            return redirect()->back()->with('success', "Synced {$synced} games.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
