<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Movies;

use App\Actions\Media\Movies\AddMovieFromTmdb;
use App\Actions\Media\Movies\DeleteMovie;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreTmdbMovieRequest;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

final class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'most_watched');

        $query = Movie::withCount('watches');

        $movies = match($sort) {
            'most_watched' => $query->orderByDesc('watches_count')->orderByDesc('created_at')->get(),
            'added'        => $query->orderByDesc('created_at')->get(),
            'alpha'        => $query->orderBy('title')->get(),
            default        => $query->orderByDesc('last_watched_at')->orderByDesc('created_at')->get(),
        };

        return view('pages.movies.index', compact('movies', 'sort'));
    }

    public function show(Movie $movie): View
    {
        $movie->load([
            'watches' => fn ($q) => $q->orderByDesc('watched_at'),
            'people' => fn ($q) => $q->wherePivot('department', 'Acting')->orderBy('pivot_cast_order'),
        ]);

        $directors = $movie->people()->wherePivot('job', 'Director')->get();

        $totalRuntime = $movie->watch_count * ($movie->runtime ?? 0);
        $averageRating = $movie->watches->whereNotNull('rating')->avg('rating');

        $watchesForAlpine = $movie->watches->map(fn ($w) => [
            'id' => $w->id,
            'date' => $w->formattedWatchedAt(),
            'rating' => $w->rating,
            'notes' => $w->notes,
        ])->values()->toArray();

        return view('pages.movies.show', compact(
            'movie', 'directors', 'totalRuntime', 'averageRating', 'watchesForAlpine',
        ));
    }

    public function store(StoreTmdbMovieRequest $request, AddMovieFromTmdb $action): JsonResponse
    {
        $movie = $action->handle((int) $request->validated('tmdb_id'));

        return response()->json([
            'id' => $movie->id,
            'title' => $movie->title,
            'year' => $movie->release_date?->year,
            'poster_url' => $movie->poster_url,
            'added' => true,
        ]);
    }

    public function refresh(Movie $movie, AddMovieFromTmdb $action): JsonResponse
    {
        Cache::forget('tmdb_movie_details_'.$movie->tmdb_id);
        $action->handle($movie->tmdb_id);

        return response()->json(['refreshed' => true]);
    }

    public function destroy(Movie $movie, DeleteMovie $action): JsonResponse
    {
        $action->handle($movie);

        return response()->json(['deleted' => true]);
    }
}
