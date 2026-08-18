<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\BacklogStatus;
use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use App\Models\SteamGame;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class BacklogController extends Controller
{
    private const SUPPORTED_TYPES = ['steam', 'playstation'];

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        if (! in_array($type, self::SUPPORTED_TYPES, strict: true)) {
            abort(404);
        }

        $validated = $request->validate([
            'backlog_status' => ['nullable', Rule::enum(BacklogStatus::class)],
        ]);

        $game = match ($type) {
            'steam'       => SteamGame::findOrFail($id),
            'playstation' => PlayStationGame::findOrFail($id),
        };

        $game->update(['backlog_status' => $validated['backlog_status']]);

        return redirect()->back()->with('success', 'Status updated.');
    }
}
