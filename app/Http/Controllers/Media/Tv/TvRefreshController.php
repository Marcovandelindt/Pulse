<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Tv;

use App\Actions\Media\Tv\RefreshSeriesFromTmdb;
use App\Http\Controllers\Controller;
use App\Models\TvSeries;
use Illuminate\Http\JsonResponse;

final class TvRefreshController extends Controller
{
    public function store(TvSeries $series, RefreshSeriesFromTmdb $action): JsonResponse
    {
        $action->handle($series);

        return response()->json(['refreshed' => true]);
    }
}
