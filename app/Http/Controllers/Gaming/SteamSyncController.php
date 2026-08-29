<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\Steam\SyncSteamGames;
use App\Http\Controllers\Controller;
use App\Models\SteamAccount;
use Illuminate\Http\RedirectResponse;

final class SteamSyncController extends Controller
{
    public function store(SyncSteamGames $action): RedirectResponse
    {
        $account = SteamAccount::active();

        if (! $account) {
            return redirect()->back()->with('error', 'No active Steam account. Add one in Settings.');
        }

        $result = $action->handle($account);

        if ($result['success']) {
            return redirect()->back()->with('success', "Synced {$result['game_count']} games for {$account->label}.");
        }

        return redirect()->back()->with('error', 'Sync failed: '.$result['error']);
    }
}
