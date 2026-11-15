<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Actions\Location\LocationPointData;
use App\Actions\Location\StoreLocationBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreOwnTracksLocationRequest;
use App\Jobs\ProcessLocationBatch;
use Illuminate\Http\JsonResponse;

final class StoreOwnTracksLocationController extends Controller
{
    public function __invoke(StoreOwnTracksLocationRequest $request, StoreLocationBatch $action): JsonResponse
    {
        // OwnTracks also sends transitions, waypoints, lwt — only process location pings
        if ($request->validated('_type') !== 'location') {
            return response()->json([]);
        }

        $data = LocationPointData::fromOwnTracks(
            payload: $request->validated(),
            deviceId: (string) $request->getUser(),
        );

        $result = $action->handle($data);

        foreach ($result->deviceRanges as $range) {
            ProcessLocationBatch::dispatch($range->deviceId, $range->from, $range->to);
        }

        // OwnTracks expects a JSON array response (may contain waypoints/commands)
        return response()->json([]);
    }
}
