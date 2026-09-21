<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\FetchGameGenres;
use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use Illuminate\Http\RedirectResponse;

final class PlayStationGenreController extends Controller
{
    public function fetch(PlayStationGame $playStationGame, FetchGameGenres $action): RedirectResponse
    {
        try {
            $message = $action->handle($playStationGame);

            return redirect()->route('playstation.show', $playStationGame)->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('playstation.show', $playStationGame)->with('error', $e->getMessage());
        }
    }
}
