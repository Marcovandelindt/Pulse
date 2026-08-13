<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Services\Spotify\SpotifyMusicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AlbumSearchController extends Controller
{
    public function __construct(
        private readonly SpotifyMusicService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'max:200']]);

        $results = $this->service->search($request->input('q'));

        $mapped = array_map(fn (array $item) => [
            'spotify_id' => $item['id'],
            'name' => $item['name'],
            'artist' => $item['artists'][0]['name'] ?? '—',
            'year' => isset($item['release_date']) ? substr($item['release_date'], 0, 4) : null,
            'image_url' => $item['images'][0]['url'] ?? null,
        ], $results);

        return response()->json($mapped);
    }
}
