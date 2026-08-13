<?php

declare(strict_types=1);

namespace App\Services\Spotify;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Track;
use Illuminate\Support\Facades\Cache;

final class SpotifyMusicService
{
    public function __construct(
        private readonly SpotifyClient $client,
    ) {}

    public function search(string $query, int $limit = 20): array
    {
        $cacheKey = 'spotify.search.albums.'.md5($query.$limit);

        return Cache::remember($cacheKey, config('spotify.cache_duration') * 60, function () use ($query, $limit) {
            $data = $this->client->get('search', [
                'q' => $query,
                'type' => 'album',
                'limit' => $limit,
            ]);

            return $data['albums']['items'] ?? [];
        });
    }

    public function getAlbum(string $spotifyId): array
    {
        return Cache::remember("spotify.album.{$spotifyId}", config('spotify.cache_duration') * 60, function () use ($spotifyId) {
            return $this->client->get("albums/{$spotifyId}");
        });
    }

    public function getAlbumTracks(string $spotifyId): array
    {
        return Cache::remember("spotify.album_tracks.{$spotifyId}", config('spotify.cache_duration') * 60, function () use ($spotifyId) {
            $tracks = [];
            $offset = 0;
            $limit = 50;

            do {
                $data = $this->client->get("albums/{$spotifyId}/tracks", compact('limit', 'offset'));
                $items = $data['items'] ?? [];
                $tracks = array_merge($tracks, $items);
                $total = $data['total'] ?? 0;
                $offset += $limit;
            } while ($offset < $total);

            return $tracks;
        });
    }

    public function getArtist(string $spotifyId): array
    {
        return Cache::remember("spotify.artist.{$spotifyId}", config('spotify.cache_duration') * 60, function () use ($spotifyId) {
            return $this->client->get("artists/{$spotifyId}");
        });
    }

    public function createAlbumFromSpotify(string $spotifyId): Album
    {
        $albumData = $this->getAlbum($spotifyId);
        $tracksData = $this->getAlbumTracks($spotifyId);

        // Primary artist
        $primaryArtistData = $albumData['artists'][0] ?? [];
        $primaryArtist = Artist::firstOrCreate(
            ['spotify_id' => $primaryArtistData['id']],
            [
                'name' => $primaryArtistData['name'],
                'image_path' => null,
                'genres' => [],
            ],
        );

        // Try to enrich artist with image/genres from full artist data
        if ($primaryArtist->wasRecentlyCreated) {
            $artistDetail = $this->getArtist($primaryArtistData['id']);
            $primaryArtist->update([
                'image_path' => $artistDetail['images'][0]['url'] ?? null,
                'genres' => $artistDetail['genres'] ?? [],
                'popularity' => $artistDetail['popularity'] ?? null,
            ]);
        }

        // Duration from tracks
        $durationMs = (int) array_sum(array_column($tracksData, 'duration_ms'));

        // Release year
        $releaseDate = $albumData['release_date'] ?? null;
        $releaseYear = $releaseDate ? (int) substr($releaseDate, 0, 4) : null;
        $releaseDateVal = match ($albumData['release_date_precision'] ?? 'day') {
            'year' => null,
            default => $releaseDate,
        };

        $album = Album::firstOrCreate(
            ['spotify_id' => $spotifyId],
            [
                'artist_id' => $primaryArtist->id,
                'name' => $albumData['name'],
                'image_path' => $albumData['images'][0]['url'] ?? null,
                'release_date' => $releaseDateVal,
                'release_year' => $releaseYear,
                'track_count' => $albumData['total_tracks'] ?? count($tracksData),
                'duration_ms' => $durationMs ?: null,
                'genres' => $albumData['genres'] ?? [],
                'album_type' => $albumData['album_type'] ?? null,
                'label' => $albumData['label'] ?? null,
            ],
        );

        if (! $album->wasRecentlyCreated) {
            return $album;
        }

        $this->syncTracks($album, $tracksData);

        // Sync all artists via pivot (primary + featuring)
        $pivotData = [];
        foreach ($albumData['artists'] ?? [] as $index => $artistData) {
            $artist = Artist::firstOrCreate(
                ['spotify_id' => $artistData['id']],
                ['name' => $artistData['name']],
            );
            $pivotData[$artist->id] = ['is_primary' => $index === 0];
        }
        $album->artists()->sync($pivotData);

        return $album;
    }

    public function syncTracks(Album $album, array $tracksData): void
    {
        foreach ($tracksData as $trackData) {
            Track::updateOrCreate(
                ['spotify_id' => $trackData['id']],
                [
                    'album_id' => $album->id,
                    'name' => $trackData['name'],
                    'track_number' => $trackData['track_number'],
                    'disc_number' => $trackData['disc_number'] ?? 1,
                    'duration_ms' => $trackData['duration_ms'] ?? null,
                    'preview_url' => $trackData['preview_url'] ?? null,
                    'is_explicit' => $trackData['explicit'] ?? false,
                ],
            );
        }
    }

    public function clearCache(string $spotifyId): void
    {
        Cache::forget("spotify.album.{$spotifyId}");
        Cache::forget("spotify.album_tracks.{$spotifyId}");
    }
}
