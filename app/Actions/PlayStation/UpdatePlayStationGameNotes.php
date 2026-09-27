<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationGame;

final class UpdatePlayStationGameNotes
{
    public function handle(PlayStationGame $game, ?string $notes): void
    {
        $game->update(['notes' => $notes ?: null]);
    }
}
