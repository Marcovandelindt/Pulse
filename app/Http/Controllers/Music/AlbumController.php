<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Actions\Music\AddAlbumFromSpotify;
use App\Actions\Music\DeleteAlbum;
use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AlbumController extends Controller
{
    public function index(): View
    {
        $albums = Album::with(['artist', 'listens'])
            ->orderByRaw('last_listened_at DESC NULLS LAST')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.music.index', compact('albums'));
    }

    public function show(Album $album): View
    {
        $album->load(['artist', 'tracks', 'listens', 'artists']);

        $averageRating = $album->listens->whereNotNull('rating')->avg('rating');

        return view('pages.music.show', compact('album', 'averageRating'));
    }

    public function store(Request $request, AddAlbumFromSpotify $action): JsonResponse
    {
        $request->validate(['spotify_id' => ['required', 'string']]);

        $album = $action->handle($request->input('spotify_id'));

        return response()->json([
            'id' => $album->id,
            'name' => $album->name,
            'redirect' => route('music.show', $album),
        ]);
    }

    public function destroy(Album $album, DeleteAlbum $action): JsonResponse
    {
        $action->handle($album);

        return response()->json(['deleted' => true]);
    }
}
