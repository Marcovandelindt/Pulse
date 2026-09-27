<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Models\PlayStationTrophy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlayStationTrophyNotesController extends Controller
{
    public function update(Request $request, PlayStationTrophy $playStationTrophy): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $playStationTrophy->update(['user_notes' => $validated['notes'] ?: null]);

        return response()->json(['user_notes' => $playStationTrophy->user_notes]);
    }
}
