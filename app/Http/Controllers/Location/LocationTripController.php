<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\LocationTrip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocationTripController extends Controller
{
    public function update(Request $request, LocationTrip $trip): JsonResponse
    {
        $validated = $request->validate([
            'dominant_motion' => ['required', 'string', 'in:driving,walking,cycling,other'],
        ]);

        $trip->update(['dominant_motion' => $validated['dominant_motion']]);

        return response()->json(['dominant_motion' => $trip->dominant_motion]);
    }
}
