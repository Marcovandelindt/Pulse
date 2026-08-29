<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationGame;

final class TogglePlayStationFavorite
{
    public function handle(PlayStationGame $game): bool
    {
        $game->update(['is_favorite' => ! $game->is_favorite]);

        return $game->is_favorite;
    }
}
