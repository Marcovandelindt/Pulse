<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Tv;

use App\Actions\Media\Tv\AddSeriesFromTmdb;
use App\Actions\Media\Tv\DeleteSeries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreTmdbSeriesRequest;
use App\Models\TvSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class TvSeriesController extends Controller
{
    public function index(): View
    {
        $series = TvSeries::orderByDesc('last_watched_at')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.tv.index', compact('series'));
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

    public function destroy(TvSeries $series, DeleteSeries $action): JsonResponse
    {
        $action->handle($series);

        return response()->json(['deleted' => true]);
    }
}
