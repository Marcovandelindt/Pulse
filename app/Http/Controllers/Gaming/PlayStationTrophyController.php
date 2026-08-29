<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\FetchPlayStationTrophies;
use App\Actions\PlayStation\TogglePlayStationTrophy;
use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use App\Models\PlayStationTrophy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class PlayStationTrophyController extends Controller
{
    public function fetch(PlayStationGame $playStationGame, FetchPlayStationTrophies $action): RedirectResponse
    {
        try {
            $message = $action->handle($playStationGame);

            return redirect()->route('playstation.show', $playStationGame)->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('playstation.show', $playStationGame)->with('error', $e->getMessage());
        }
    }

    public function toggle(PlayStationTrophy $playStationTrophy, TogglePlayStationTrophy $action): JsonResponse
    {
        return response()->json($action->handle($playStationTrophy));
    }
}
