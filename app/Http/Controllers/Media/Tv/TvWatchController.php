<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Tv;

use App\Actions\Media\Tv\BulkMarkSeriesWatched;
use App\Actions\Media\Tv\DeleteEpisodeWatch;
use App\Actions\Media\Tv\EpisodeWatchData;
use App\Actions\Media\Tv\MarkEpisodeWatched;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\BulkWatchRequest;
use App\Http\Requests\Media\MarkEpisodeWatchedRequest;
use App\Models\EpisodeWatch;
use App\Models\TvEpisode;
use App\Models\TvSeries;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

final class TvWatchController extends Controller
{
    public function store(
        MarkEpisodeWatchedRequest $request,
        TvEpisode $episode,
        MarkEpisodeWatched $action,
    ): JsonResponse {
        $watch = $action->handle($episode, EpisodeWatchData::fromRequest($request));

        $episode->load('season.series');
        $series = $episode->season->series;

        return response()->json([
            'watch_id' => $watch->id,
            'formatted_date' => $watch->formattedWatchedAt(),
            'episodes_watched' => $series->episodes_watched,
            'completion_percentage' => $series->completion_percentage,
        ]);
    }

    public function bulkStore(
        BulkWatchRequest $request,
        TvSeries $series,
        BulkMarkSeriesWatched $action,
    ): JsonResponse {
        $watchedAt = $request->validated('watched_at')
            ? Carbon::parse($request->validated('watched_at'))
            : null;

        $action->handle($series, $watchedAt, (bool) $request->validated('year_only', false));

        $series->refresh();

        return response()->json([
            'episodes_watched' => $series->episodes_watched,
            'completion_percentage' => $series->completion_percentage,
        ]);
    }

    public function destroy(EpisodeWatch $watch, DeleteEpisodeWatch $action): JsonResponse
    {
        $action->handle($watch);

        return response()->json(['deleted' => true]);
    }
}
