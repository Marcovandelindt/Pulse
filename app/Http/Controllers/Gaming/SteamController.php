<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\SteamAccount;
use App\Models\SteamGame;
use App\Services\Steam\SteamApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SteamController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = SteamAccount::orderBy('label')->get();
        $activeAccount = SteamAccount::active();

        $search = $request->get('search', '');
        $sort = $request->get('sort', 'playtime');
        $status = $request->get('status', '');

        $baseQuery = $activeAccount
            ? SteamGame::where('steam_account_id', $activeAccount->id)
            : SteamGame::whereRaw('0 = 1');

        if ($search !== '') {
            $baseQuery->where('name', 'like', "%{$search}%");
        }

        if ($status !== '') {
            $baseQuery->where('backlog_status', $status);
        }

        $query = match ($sort) {
            'name'        => (clone $baseQuery)->orderBy('name'),
            'playtime_2w' => (clone $baseQuery)->orderByDesc('playtime_2weeks_minutes'),
            'last_played' => (clone $baseQuery)->orderByDesc('last_played_at'),
            default       => (clone $baseQuery)->orderByDesc('playtime_minutes'),
        };

        $games = $query->paginate(25)->withQueryString();

        $totalGames = (clone $baseQuery)->count();
        $totalSpent = (clone $baseQuery)->whereNotNull('price')->sum('price');
        $totalHours = round((clone $baseQuery)->sum('playtime_minutes') / 60, 1);
        $recentHours = round(((clone $baseQuery)->sum('playtime_2weeks_minutes') ?? 0) / 60, 1);

        $mostPlayed = (clone $baseQuery)->orderByDesc('playtime_minutes')->limit(5)->get();
        $recentlyPlayed = (clone $baseQuery)->orderByDesc('last_played_at')->whereNotNull('last_played_at')->limit(5)->get();

        $backlogStatuses = BacklogStatus::cases();

        return view('pages.steam.index', compact(
            'accounts',
            'activeAccount',
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
        $account = SteamAccount::active();

        if (! $account) {
            return redirect()->back()->with('error', 'No active Steam account. Add one in Settings.');
        }

        $result = app(SteamApiService::class)->syncGames($account);

        if ($result['success']) {
            return redirect()->back()->with('success', "Synced {$result['game_count']} games for {$account->label}.");
        }

        return redirect()->back()->with('error', 'Sync failed: '.$result['error']);
    }

    public function settings(): View
    {
        $accounts = SteamAccount::orderBy('label')->get();

        return view('pages.steam.settings', compact('accounts'));
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label'    => ['required', 'string', 'max:64'],
            'steam_id' => ['required', 'string', 'max:64'],
            'api_key'  => ['required', 'string', 'max:255'],
        ]);

        $isFirst = SteamAccount::count() === 0;

        SteamAccount::create([
            ...$validated,
            'is_active' => $isFirst,
        ]);

        return redirect()->route('steam.settings')->with('success', "Account \"{$validated['label']}\" added.");
    }

    public function activateAccount(SteamAccount $account): RedirectResponse
    {
        $account->activate();

        return redirect()->route('steam.index')->with('success', "Switched to {$account->label}.");
    }

    public function destroyAccount(SteamAccount $account): RedirectResponse
    {
        $wasActive = $account->is_active;
        $account->delete();

        if ($wasActive) {
            SteamAccount::first()?->activate();
        }

        return redirect()->route('steam.settings')->with('success', 'Account removed.');
    }

    public function testConnection(Request $request): JsonResponse
    {
        $accountId = $request->input('account_id');
        $account = $accountId
            ? SteamAccount::findOrFail($accountId)
            : SteamAccount::active();

        if (! $account) {
            return response()->json(['success' => false, 'error' => 'No Steam account configured.'], 422);
        }

        $result = app(SteamApiService::class)->testConnection($account);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
