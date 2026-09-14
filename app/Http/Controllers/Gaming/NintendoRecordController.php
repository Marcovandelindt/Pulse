<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\NintendoDailyRecord;
use App\Models\NintendoGame;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NintendoRecordController extends Controller
{
    public function index(Request $request): View
    {
        $records = NintendoDailyRecord::query()
            ->with('game')
            ->orderByDesc('date')
            ->paginate(50);

        return view('pages.nintendo.sessions', compact('records'));
    }

    public function store(Request $request, NintendoGame $game): RedirectResponse
    {
        $data = $request->validate([
            'date'           => ['required', 'date', 'before_or_equal:today'],
            'minutes_played' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $game->dailyRecords()->updateOrCreate(
            ['date' => $data['date']],
            ['minutes_played' => $data['minutes_played']],
        );

        $this->recalculateTotals($game);

        return redirect()->route('nintendo.show', $game)
            ->with('success', 'Session logged.');
    }

    public function destroy(NintendoGame $game, NintendoDailyRecord $record): RedirectResponse
    {
        $record->delete();

        $this->recalculateTotals($game);

        return redirect()->route('nintendo.show', $game)
            ->with('success', 'Session removed.');
    }

    private function recalculateTotals(NintendoGame $game): void
    {
        $game->update([
            'total_minutes'   => $game->dailyRecords()->sum('minutes_played'),
            'first_played_at' => $game->dailyRecords()->min('date'),
            'last_played_at'  => $game->dailyRecords()->max('date'),
        ]);
    }
}
