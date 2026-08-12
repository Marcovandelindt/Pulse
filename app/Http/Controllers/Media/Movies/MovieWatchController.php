<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Movies;

use App\Actions\Media\Movies\DeleteMovieWatch;
use App\Actions\Media\Movies\MarkMovieWatched;
use App\Actions\Media\Movies\MovieWatchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\MarkMovieWatchedRequest;
use App\Models\Movie;
use App\Models\MovieWatch;
use Illuminate\Http\JsonResponse;

final class MovieWatchController extends Controller
{
    public function store(MarkMovieWatchedRequest $request, Movie $movie, MarkMovieWatched $action): JsonResponse
    {
        $watch = $action->handle($movie, MovieWatchData::fromRequest($request));
        $movie->refresh();

        return response()->json([
            'watch_id' => $watch->id,
            'formatted_date' => $watch->formattedWatchedAt(),
            'watch_count' => $movie->watch_count,
        ]);
    }

    public function destroy(MovieWatch $watch, DeleteMovieWatch $action): JsonResponse
    {
        $action->handle($watch);

        return response()->json(['deleted' => true]);
    }
}
