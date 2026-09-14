<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\Nintendo\SyncNintendoData;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class NintendoSyncController extends Controller
{
    public function store(SyncNintendoData $action): RedirectResponse
    {
        try {
            $count = $action->handle();

            return redirect()->route('nintendo.index')
                ->with('success', "Synced {$count} play records from Nintendo Switch.");
        } catch (\Throwable $e) {
            return redirect()->route('nintendo.index')
                ->with('error', $e->getMessage());
        }
    }
}
