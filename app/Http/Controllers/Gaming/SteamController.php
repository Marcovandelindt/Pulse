<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\SteamGame;
use App\Services\Steam\SteamApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SteamController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'playtime');
        $status = $request->get('status', '');

        $query = SteamGame::query();

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status !== '') {
            $query->where('backlog_status', $status);
        }

        $query = match ($sort) {
            'name'          => $query->orderBy('name'),
            'playtime_2w'   => $query->orderByDesc('playtime_2weeks_minutes'),
            'last_played'   => $query->orderByDesc('last_played_at'),
            default         => $query->orderByDesc('playtime_minutes'),
        };

        $games = $query->paginate(25)->withQueryString();

        $totalGames = SteamGame::count();
        $totalSpent = SteamGame::whereNotNull('price')->sum('price');
        $totalHours = round(SteamGame::sum('playtime_minutes') / 60, 1);
        $recentHours = round((SteamGame::sum('playtime_2weeks_minutes') ?? 0) / 60, 1);

        $mostPlayed = SteamGame::mostPlayed(5)->get();
        $recentlyPlayed = SteamGame::recentlyPlayed(5)->whereNotNull('last_played_at')->get();

        $backlogStatuses = BacklogStatus::cases();

        return view('pages.steam.index', compact(
            'games',
            'search',
            'sort',
            'status',
            'totalGames',
            'totalSpent',
            'totalHours',
            'recentHours',
            'mostPlayed',
            'recentlyPlayed',
            'backlogStatuses',
        ));
    }

    public function show(SteamGame $game): View
    {
        $game->load('genres');

        $costPerHour = ($game->price && $game->playtime_hours > 0)
            ? round((float) $game->price / $game->playtime_hours, 2)
            : null;

        $costPerMinute = ($game->price && $game->playtime_minutes > 0)
            ? round((float) $game->price / $game->playtime_minutes, 4)
            : null;

        $valueRating = null;
        if ($costPerHour !== null) {
            $valueRating = match (true) {
                $costPerHour < 1  => 'Excellent',
                $costPerHour < 3  => 'Good',
                $costPerHour < 6  => 'OK',
                default           => 'Poor',
            };
        }

        return view('pages.steam.show', compact(
            'game',
            'costPerHour',
            'costPerMinute',
            'valueRating',
        ));
    }

    public function edit(SteamGame $game): View
    {
        $genres = Genre::orderBy('name')->get();
        $playModes = PlayMode::cases();
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

    public function sync(): RedirectResponse
    {
        $result = app(SteamApiService::class)->syncGames();

        if ($result['success']) {
            return redirect()->back()->with('success', "Synced {$result['game_count']} games from Steam.");
        }

        return redirect()->back()->with('error', 'Sync failed: '.$result['error']);
    }

    public function settings(): View
    {
        $apiKeyConfigured = ! empty(config('services.steam.api_key'));
        $steamId = config('services.steam.steam_id', '');

        return view('pages.steam.settings', compact('apiKeyConfigured', 'steamId'));
    }

    public function testConnection(): JsonResponse
    {
        $result = app(SteamApiService::class)->testConnection();

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
