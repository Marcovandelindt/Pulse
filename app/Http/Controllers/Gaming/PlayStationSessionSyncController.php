<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Services\PlayStation\PlayStationScraperService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PlayStationSessionSyncController extends Controller
{
    public function store(Request $request, PlayStationScraperService $scraper): RedirectResponse
    {
        $request->validate([
            'cookie' => ['nullable', 'string', 'max:2000'],
        ]);

        $cookie = $request->filled('cookie') ? trim($request->string('cookie')->toString()) : null;

        if ($cookie !== null) {
            $scraper->setSessionCookie($cookie);
        }

        try {
            $username = config('services.playstation.username');
            $synced = $scraper->syncSessions($username);

            return redirect()->back()->with('success', "Synced {$synced} new PlayStation session" . ($synced !== 1 ? 's' : '') . '.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
