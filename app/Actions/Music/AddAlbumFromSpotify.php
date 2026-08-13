<?php

declare(strict_types=1);

namespace App\Actions\Music;

use App\Models\Album;
use App\Services\Spotify\SpotifyMusicService;

final class AddAlbumFromSpotify
{
    public function __construct(
        private readonly SpotifyMusicService $service,
    ) {}

    public function handle(string $spotifyId): Album
    {
        $existing = Album::where('spotify_id', $spotifyId)->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->service->createAlbumFromSpotify($spotifyId);
    }
}
