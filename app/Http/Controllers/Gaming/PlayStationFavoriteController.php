<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\TogglePlayStationFavorite;
use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use Illuminate\Http\JsonResponse;

final class PlayStationFavoriteController extends Controller
{
    public function toggle(PlayStationGame $playStationGame, TogglePlayStationFavorite $action): JsonResponse
    {
        return response()->json(['is_favorite' => $action->handle($playStationGame)]);
    }
}
