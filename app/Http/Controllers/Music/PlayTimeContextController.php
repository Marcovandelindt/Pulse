<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Play;
use Illuminate\Http\JsonResponse;

final class PlayTimeContextController extends Controller
{
    public function update(Play $play): JsonResponse
    {
        $next = match ($play->time_context) {
            null => 'work',
            'work' => 'free',
            default => null,
        };

        $play->update(['time_context' => $next]);

        return response()->json(['time_context' => $next]);
    }
}
