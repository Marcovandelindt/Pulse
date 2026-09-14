<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\Nintendo\CreateNintendoGame;
use App\Http\Controllers\Controller;
use App\Models\NintendoDailyRecord;
use App\Models\NintendoGame;
use App\Services\Nintendo\IgdbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NintendoController extends Controller
{
    public function index(): View
    {
        $games = NintendoGame::query()
            ->orderByDesc('last_played_at')
            ->orderBy('name')
            ->get();

        $totalMinutes = $games->sum('total_minutes');
        $totalGames   = $games->count();
        $lastSynced   = $games->max('last_synced_at');

        $recentActivity = NintendoDailyRecord::query()
            ->where('date', '>=', now()->subDays(30))
            ->count();

        return view('pages.nintendo.index', compact(
            'games',
            'totalMinutes',
            'totalGames',
            'lastSynced',
            'recentActivity',
        ));
    }

    public function create(): View
    {
        return view('pages.nintendo.create');
    }

    public function store(Request $request, CreateNintendoGame $action): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'igdb_id'     => ['nullable', 'integer'],
            'cover_url'   => ['nullable', 'url'],
            'genres'      => ['nullable', 'array'],
            'released_at' => ['nullable', 'date'],
        ]);

        $game = $action->handle($data);

        return redirect()->route('nintendo.show', $game)
            ->with('success', "{$game->name} added.");
    }

    public function show(NintendoGame $game): View
    {
        $records = $game->dailyRecords()
            ->orderByDesc('date')
            ->get();

        return view('pages.nintendo.show', compact('game', 'records'));
    }

    public function destroy(NintendoGame $game): RedirectResponse
    {
        $game->delete();

        return redirect()->route('nintendo.index')
            ->with('success', "{$game->name} removed.");
    }

    public function search(Request $request, IgdbService $igdb): JsonResponse
    {
        $query   = $request->string('q')->toString();
        $results = $igdb->search($query);

        return response()->json($results);
    }
}
