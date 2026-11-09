<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Actions\Location\StoreLocationBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreOverlandBatchRequest;
use App\Jobs\ProcessLocationBatch;
use Illuminate\Http\JsonResponse;

final class StoreOverlandBatchController extends Controller
{
    public function __invoke(StoreOverlandBatchRequest $request, StoreLocationBatch $action): JsonResponse
    {
        $result = $action->handle($request->validated('locations'));

        foreach ($result->deviceRanges as $range) {
            ProcessLocationBatch::dispatch($range->deviceId, $range->from, $range->to);
        }

        return response()->json(['result' => 'ok']);
    }
}
