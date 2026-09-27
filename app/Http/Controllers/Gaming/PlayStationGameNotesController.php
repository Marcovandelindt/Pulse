<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\UpdatePlayStationGameNotes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gaming\UpdateGameNotesRequest;
use App\Models\PlayStationGame;
use Illuminate\Http\RedirectResponse;

final class PlayStationGameNotesController extends Controller
{
    public function update(UpdateGameNotesRequest $request, PlayStationGame $playStationGame, UpdatePlayStationGameNotes $action): RedirectResponse
    {
        $action->handle($playStationGame, $request->validated('notes'));

        return redirect()->route('playstation.show', $playStationGame)->with('success', 'Notes saved.');
    }
}
