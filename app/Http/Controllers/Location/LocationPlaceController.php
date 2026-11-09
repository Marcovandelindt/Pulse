<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocationPlaceController extends Controller
{
    public function update(Request $request, Place $place): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $place->update(['name' => $validated['name']]);

        return response()->json(['name' => $place->fresh()?->name]);
    }
}
