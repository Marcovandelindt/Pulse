<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\TogglePlayStationTrophy;
use App\Http\Controllers\Controller;
use App\Models\PlayStationTrophy;
use Illuminate\Http\JsonResponse;

final class PlayStationTrophyController extends Controller
{
    public function toggle(PlayStationTrophy $playStationTrophy, TogglePlayStationTrophy $action): JsonResponse
    {
        return response()->json($action->handle($playStationTrophy));
    }
}
