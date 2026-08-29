<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\PlayStationTrophy;
use Illuminate\Http\JsonResponse;

final class PlayStationTrophyController extends Controller
{
    public function toggle(PlayStationTrophy $playStationTrophy): JsonResponse
    {
        $isEarned = ! $playStationTrophy->is_earned;

        $playStationTrophy->update([
            'is_earned' => $isEarned,
            'earned_at' => $isEarned ? now() : null,
        ]);

        return response()->json([
            'is_earned' => $isEarned,
            'earned_at' => $isEarned ? $playStationTrophy->fresh()->earned_at->format('d M Y') : null,
        ]);
    }
}
