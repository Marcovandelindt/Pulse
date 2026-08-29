<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationTrophy;

final class TogglePlayStationTrophy
{
    public function handle(PlayStationTrophy $trophy): array
    {
        $isEarned = ! $trophy->is_earned;

        $trophy->update([
            'is_earned' => $isEarned,
            'earned_at' => $isEarned ? now() : null,
        ]);

        return [
            'is_earned' => $isEarned,
            'earned_at' => $isEarned ? $trophy->fresh()->earned_at->format('d M Y') : null,
        ];
    }
}
