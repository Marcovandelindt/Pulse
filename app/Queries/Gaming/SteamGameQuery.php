<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Models\SteamGame;

final class SteamGameQuery
{
    /** @return array<string, mixed> */
    public function handle(SteamGame $game): array
    {
        $game->load('genres', 'tracks.artists');

        $costPerHour = ($game->price && $game->playtime_hours > 0)
            ? round((float) $game->price / $game->playtime_hours, 2)
            : null;

        $costPerMinute = ($game->price && $game->playtime_minutes > 0)
            ? round((float) $game->price / $game->playtime_minutes, 4)
            : null;

        $valueRating = null;
        if ($costPerHour !== null) {
            $valueRating = match (true) {
                $costPerHour < 1 => 'Excellent',
                $costPerHour < 3 => 'Good',
                $costPerHour < 6 => 'OK',
                default          => 'Poor',
            };
        }

        return compact('game', 'costPerHour', 'costPerMinute', 'valueRating');
    }
}
