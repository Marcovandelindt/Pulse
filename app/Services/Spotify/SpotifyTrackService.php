<?php

declare(strict_types=1);

namespace App\Services\Spotify;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Play;
use App\Models\SpotifySyncCursor;
use App\Models\Track;
use Illuminate\Support\Carbon;

final class SpotifyTrackService
{
    public function __construct(
        private readonly SpotifyService $spotify,
    ) {}

    public function syncRecentlyPlayed(): array
    {
        $cursor = SpotifySyncCursor::lastPlayedAt();
        $params = ['limit' => 50];

        if ($cursor !== null) {
            $params['after'] = $cursor->timestamp * 1000;
        }

        $response = $this->spotify->get('/me/player/recently-played', $params);

        if ($response === null || empty($response['items'])) {
            return ['synced' => 0, 'skipped' => 0, 'total' => 0];
        }

        $items = $response['items'];
        $synced = 0;
        $skipped = 0;
        $maxPlayedAt = null;

        foreach ($items as $item) {
            $trackData = $item['track'];
            $playedAt = Carbon::parse($item['played_at'], 'UTC')->setTimezone(config('app.timezone'));

            if ($maxPlayedAt === null || $playedAt->gt($maxPlayedAt)) {
                $maxPlayedAt = $playedAt;
            }

            $artistModels = [];
            foreach ($trackData['artists'] as $artistData) {
                $artist = Artist::firstOrCreate(
                    ['spotify_artist_id' => $artistData['id']],
                    ['name' => $artistData['name']],
                );

                if ($artist->wasRecentlyCreated || $artist->image_url === null) {
                    $artistDetails = $this->spotify->get('/artists/'.$artistData['id']);

                    if ($artistDetails !== null) {
                        $artist->update([
                            'image_url' => $artistDetails['images'][0]['url'] ?? null,
                            'genres' => $artistDetails['genres'] ?? [],
                            'popularity' => $artistDetails['popularity'] ?? null,
                        ]);
                    }
                }

                $artistModels[] = $artist;
            }

            $albumData = $trackData['album'];
            $album = Album::firstOrCreate(
                ['spotify_album_id' => $albumData['id']],
                [
                    'name' => $albumData['name'],
                    'image_url' => $albumData['images'][0]['url'] ?? null,
                    'album_type' => $albumData['album_type'] ?? null,
                    'total_tracks' => $albumData['total_tracks'] ?? null,
                ],
            );

            if ($album->wasRecentlyCreated) {
                $albumDetails = $this->spotify->get('/albums/'.$albumData['id']);

                if ($albumDetails !== null) {
                    $album->update([
                        'release_date' => $albumDetails['release_date'] ?? null,
                    ]);
                }
            }

            $track = Track::firstOrCreate(
                ['spotify_track_id' => $trackData['id']],
                [
                    'album_id' => $album->id,
                    'title' => $trackData['name'],
                    'duration_ms' => $trackData['duration_ms'] ?? null,
                    'popularity' => $trackData['popularity'] ?? null,
                    'preview_url' => $trackData['preview_url'] ?? null,
                    'spotify_uri' => $trackData['uri'] ?? null,
                    'is_explicit' => $trackData['explicit'] ?? false,
                ],
            );

            $pivotData = collect($trackData['artists'])->mapWithKeys(
                fn ($artistData, int $index) => [
                    $artistModels[$index]->id => [
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ],
                ]
            )->toArray();

            $track->artists()->syncWithoutDetaching($pivotData);

            $inserted = Play::insertOrIgnore([
                'track_id' => $track->id,
                'played_at' => $playedAt,
                'source' => 'spotify',
                'context' => isset($item['context']) ? json_encode($item['context']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted > 0) {
                $synced++;
            } else {
                $skipped++;
            }
        }

        if ($maxPlayedAt !== null) {
            SpotifySyncCursor::record($maxPlayedAt, $synced);
        }

        return ['synced' => $synced, 'skipped' => $skipped, 'total' => count($items)];
    }

    public function getCurrentlyPlaying(): ?array
    {
        $response = $this->spotify->get('/me/player/currently-playing');

        if ($response === null || empty($response['is_playing'])) {
            return null;
        }

        $track = $response['item'] ?? null;

        if ($track === null) {
            return null;
        }

        return [
            'track_name' => $track['name'],
            'artist_names' => collect($track['artists'])->pluck('name')->implode(', '),
            'album_name' => $track['album']['name'] ?? null,
            'album_image_url' => $track['album']['images'][0]['url'] ?? null,
            'is_playing' => $response['is_playing'],
            'progress_ms' => $response['progress_ms'] ?? 0,
            'duration_ms' => $track['duration_ms'] ?? 0,
            'spotify_uri' => $track['uri'] ?? null,
        ];
    }
}
