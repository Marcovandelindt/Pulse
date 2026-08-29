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

        $timezone = config('app.timezone');

        // Build play list, upsert tracks
        $plays = [];
        foreach ($response['items'] as $item) {
            $track   = $this->upsertTrack($item['track'], fetchDetails: true);
            $plays[] = [
                'track_id'    => $track->id,
                'duration_ms' => $track->duration_ms,
                'played_at'   => Carbon::parse($item['played_at'], 'UTC')->setTimezone($timezone),
                'context'     => isset($item['context']) ? json_encode($item['context']) : null,
            ];
        }

        // Process chronologically so each play can be checked against the previous
        usort($plays, fn ($a, $b) => $a['played_at'] <=> $b['played_at']);

        // Pre-fetch the most recent existing play per track to seed the duplicate check
        $trackIds   = array_unique(array_column($plays, 'track_id'));
        $lastSeenAt = Play::whereIn('track_id', $trackIds)
            ->where('played_at', '>=', $plays[0]['played_at']->clone()->subDay())
            ->selectRaw('track_id, MAX(played_at) as last_played_at')
            ->groupBy('track_id')
            ->pluck('last_played_at', 'track_id')
            ->map(fn ($d) => Carbon::parse($d))
            ->toArray();

        $synced      = 0;
        $skipped     = 0;
        $maxPlayedAt = null;

        foreach ($plays as $play) {
            $trackId  = $play['track_id'];
            $playedAt = $play['played_at'];

            if ($maxPlayedAt === null || $playedAt->gt($maxPlayedAt)) {
                $maxPlayedAt = $playedAt;
            }

            if ($this->isDuplicatePlay($playedAt, $lastSeenAt[$trackId] ?? null, $play['duration_ms'])) {
                $skipped++;
                continue;
            }

            $inserted = Play::insertOrIgnore([
                'track_id'   => $trackId,
                'played_at'  => $playedAt,
                'source'     => 'spotify',
                'context'    => $play['context'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted > 0) {
                $synced++;
                $lastSeenAt[$trackId] = $playedAt;
            } else {
                $skipped++;
            }
        }

        if ($maxPlayedAt !== null) {
            SpotifySyncCursor::record($maxPlayedAt, $synced);
        }

        return ['synced' => $synced, 'skipped' => $skipped, 'total' => count($response['items'])];
    }

    private function isDuplicatePlay(Carbon $playedAt, ?Carbon $lastPlayedAt, ?int $durationMs): bool
    {
        if ($lastPlayedAt === null) {
            return false;
        }

        $gapMs    = abs($playedAt->diffInMilliseconds($lastPlayedAt));
        $minGapMs = max($durationMs ?? 0, 5_000);

        return $gapMs < $minGapMs;
    }

    public function upsertTrack(array $trackData, bool $fetchDetails = false): Track
    {
        $artistModels = [];

        foreach ($trackData['artists'] as $artistData) {
            $artist = Artist::firstOrCreate(
                ['spotify_artist_id' => $artistData['id']],
                ['name' => $artistData['name']],
            );

            if ($fetchDetails && ($artist->wasRecentlyCreated || $artist->image_url === null)) {
                $artistDetails = $this->spotify->get('/artists/'.$artistData['id']);

                if ($artistDetails !== null) {
                    $artist->update([
                        'image_url'  => $artistDetails['images'][0]['url'] ?? null,
                        'genres'     => $artistDetails['genres'] ?? [],
                        'popularity' => $artistDetails['popularity'] ?? null,
                    ]);
                }
            }

            $artistModels[] = $artist;
        }

        $albumData = $trackData['album'];
        $album     = Album::firstOrCreate(
            ['spotify_album_id' => $albumData['id']],
            [
                'name'         => $albumData['name'],
                'image_url'    => $albumData['images'][0]['url'] ?? null,
                'album_type'   => $albumData['album_type'] ?? null,
                'total_tracks' => $albumData['total_tracks'] ?? null,
            ],
        );

        if ($fetchDetails && $album->wasRecentlyCreated) {
            $albumDetails = $this->spotify->get('/albums/'.$albumData['id']);

            if ($albumDetails !== null) {
                $album->update(['release_date' => $albumDetails['release_date'] ?? null]);
            }
        }

        $track = Track::firstOrCreate(
            ['spotify_track_id' => $trackData['id']],
            [
                'album_id'    => $album->id,
                'title'       => $trackData['name'],
                'duration_ms' => $trackData['duration_ms'] ?? null,
                'popularity'  => $trackData['popularity'] ?? null,
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

        return $track;
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
            'track_name'       => $track['name'],
            'spotify_track_id' => $track['id'] ?? null,
            'raw_track'        => $track,
            'artists'          => collect($track['artists'])->map(fn ($a) => [
                'name'              => $a['name'],
                'spotify_artist_id' => $a['id'],
            ])->all(),
            'artist_names'    => collect($track['artists'])->pluck('name')->implode(', '),
            'album_name'      => $track['album']['name'] ?? null,
            'album_image_url' => $track['album']['images'][0]['url'] ?? null,
            'is_playing'      => $response['is_playing'],
            'progress_ms'     => $response['progress_ms'] ?? 0,
            'duration_ms'     => $track['duration_ms'] ?? 0,
            'spotify_uri'     => $track['uri'] ?? null,
        ];
    }
}
