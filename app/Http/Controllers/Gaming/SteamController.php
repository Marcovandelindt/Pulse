<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\SteamGame;
use App\Queries\Gaming\SteamGameQuery;
use App\Queries\Gaming\SteamIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SteamController extends Controller
{
    public function index(Request $request, SteamIndexQuery $query): View
    {
        return view('pages.steam.index', $query->handle(
            sort: $request->get('sort', 'playtime'),
            search: $request->get('search', ''),
            status: $request->get('status', ''),
        ));
    }

    public function show(SteamGame $game, SteamGameQuery $query): View
    {
        return view('pages.steam.show', $query->handle($game));
    }

    public function edit(SteamGame $game): View
    {
        $genres          = Genre::orderBy('name')->get();
        $playModes       = PlayMode::cases();
        $backlogStatuses = BacklogStatus::cases();

        return view('pages.steam.edit', compact('game', 'genres', 'playModes', 'backlogStatuses'));
    }

    public function update(Request $request, SteamGame $game): RedirectResponse
    {
        $validated = $request->validate([
            'price'                => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'play_mode'            => ['nullable', Rule::enum(PlayMode::class)],
            'main_story_completed' => ['nullable', 'boolean'],
            'user_rating'          => ['nullable', 'integer', 'min:1', 'max:10'],
            'critic_rating'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'backlog_status'       => ['nullable', Rule::enum(BacklogStatus::class)],
            'genres'               => ['nullable', 'array'],
            'genres.*'             => ['integer', 'exists:genres,id'],
        ]);

        $genreIds = $validated['genres'] ?? [];
        unset($validated['genres']);

        $game->update($validated);
        $game->genres()->sync($genreIds);

        return redirect()->route('steam.games.show', $game)->with('success', 'Game updated.');
    }
}
