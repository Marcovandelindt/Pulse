<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Actions\Music\DeleteAlbumListen;
use App\Actions\Music\LogAlbumListen;
use App\Data\AlbumListenData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Music\LogAlbumListenRequest;
use App\Models\Album;
use App\Models\AlbumListen;
use Illuminate\Http\JsonResponse;

final class AlbumListenController extends Controller
{
    public function store(LogAlbumListenRequest $request, Album $album, LogAlbumListen $action): JsonResponse
    {
        $listen = $action->handle($album, AlbumListenData::fromRequest($request));
        $album->refresh();

        return response()->json([
            'listen_id' => $listen->id,
            'formatted_date' => $listen->formattedListenedAt(),
            'listen_count' => $album->listen_count,
        ]);
    }

    public function destroy(AlbumListen $listen, DeleteAlbumListen $action): JsonResponse
    {
        $action->handle($listen);

        return response()->json(['deleted' => true]);
    }
}
