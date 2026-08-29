<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Enums\BacklogStatus;
use App\Models\SteamAccount;
use App\Models\SteamGame;

final class SteamIndexQuery
{
    /** @return array<string, mixed> */
    public function handle(string $sort, string $search, string $status): array
    {
        $accounts      = SteamAccount::orderBy('label')->get();
        $activeAccount = SteamAccount::active();

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

        $games          = $query->paginate(25)->withQueryString();
        $totalGames     = (clone $baseQuery)->count();
        $totalSpent     = (clone $baseQuery)->whereNotNull('price')->sum('price');
        $totalHours     = round((clone $baseQuery)->sum('playtime_minutes') / 60, 1);
        $recentHours    = round(((clone $baseQuery)->sum('playtime_2weeks_minutes') ?? 0) / 60, 1);
        $mostPlayed     = (clone $baseQuery)->orderByDesc('playtime_minutes')->limit(5)->get();
        $recentlyPlayed = (clone $baseQuery)->orderByDesc('last_played_at')->whereNotNull('last_played_at')->limit(5)->get();
        $backlogStatuses = BacklogStatus::cases();

        return compact(
            'accounts', 'activeAccount', 'games', 'search', 'sort', 'status',
            'totalGames', 'totalSpent', 'totalHours', 'recentHours',
            'mostPlayed', 'recentlyPlayed', 'backlogStatuses',
        );
    }
}
