<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Tv;

use App\Actions\Media\Tv\AddSeriesFromTmdb;
use App\Actions\Media\Tv\DeleteSeries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreTmdbSeriesRequest;
use App\Models\TvSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TvSeriesController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'most_watched');

        $query = TvSeries::query();

        $series = match($sort) {
            'most_watched' => $query->orderByDesc('watched_runtime_minutes')->orderByDesc('created_at')->get(),
            'added'        => $query->orderByDesc('created_at')->get(),
            'alpha'        => $query->orderBy('name')->get(),
            default        => $query->orderByDesc('last_watched_at')->orderByDesc('created_at')->get(),
        };

        return view('pages.tv.index', compact('series', 'sort'));
    }

    public function show(TvSeries $series): View
    {
        $series->load([
            'seasons.episodes.watches',
            'people' => fn ($q) => $q->wherePivot('department', 'Acting')->orderByPivot('episode_count', 'desc'),
        ]);

        return view('pages.tv.show', compact('series'));
    }

    public function store(StoreTmdbSeriesRequest $request, AddSeriesFromTmdb $action): JsonResponse
    {
        $series = $action->handle((int) $request->validated('tmdb_id'));

        return response()->json([
            'id' => $series->id,
            'name' => $series->name,
            'poster_url' => $series->poster_url,
            'added' => true,
        ]);
    }

    public function favorite(TvSeries $series): JsonResponse
    {
        $series->update(['is_favorite' => ! $series->is_favorite]);

        return response()->json(['is_favorite' => $series->is_favorite]);
    }

    public function rate(Request $request, TvSeries $series): JsonResponse
    {
        $rating = $request->validate(['rating' => ['nullable', 'numeric', 'min:1', 'max:10']])['rating'];

        $series->update(['user_rating' => $rating ? round((float) $rating, 1) : null]);

        return response()->json(['user_rating' => $series->user_rating]);
    }

    public function destroy(TvSeries $series, DeleteSeries $action): JsonResponse
    {
        $action->handle($series);

        return response()->json(['deleted' => true]);
    }
}
