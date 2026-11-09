<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Actions\Location\StoreLocationBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\StoreOverlandBatchRequest;
use Illuminate\Http\JsonResponse;

final class StoreOverlandBatchController extends Controller
{
    public function __invoke(StoreOverlandBatchRequest $request, StoreLocationBatch $action): JsonResponse
    {
        $action->handle($request->validated('locations'));

        return response()->json(['result' => 'ok']);
    }
}
