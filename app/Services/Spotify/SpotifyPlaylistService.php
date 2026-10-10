<?php

declare(strict_types=1);

namespace App\Services\Spotify;

use RuntimeException;

final class SpotifyPlaylistService
{
    public function __construct(
        private readonly SpotifyService $spotify,
    ) {}

    public function create(string $userId, string $name, string $description): string
    {
        $response = $this->spotify->post("/users/{$userId}/playlists", [
            'name'        => $name,
            'description' => $description,
            'public'      => false,
        ]);

        if (! isset($response['id'])) {
            throw new RuntimeException('Failed to create Spotify playlist. Check that your Spotify account has the playlist scope authorized.');
        }

        return $response['id'];
    }

    /** @param string[] $uris */
    public function addTracks(string $playlistId, array $uris): void
    {
        foreach (array_chunk($uris, 100) as $chunk) {
            $result = $this->spotify->post("/playlists/{$playlistId}/tracks", [
                'uris' => $chunk,
            ]);

            if ($result === null) {
                throw new RuntimeException('Failed to add tracks to Spotify playlist.');
            }
        }
    }

    public function getUrl(string $playlistId): string
    {
        return "https://open.spotify.com/playlist/{$playlistId}";
    }
}
