<?php

declare(strict_types=1);

namespace App\Services\Nintendo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class IgdbService
{
    private const TOKEN_URL = 'https://id.twitch.tv/oauth2/token';
    private const API_URL         = 'https://api.igdb.com/v4';

    public function search(string $query): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $response = Http::withHeaders($this->headers())
            ->withBody(
                'search "' . addslashes($query) . '";'
                . ' fields name,cover.image_id,genres.name,first_release_date;'
                . ' limit 8;',
                'text/plain'
            )
            ->post(self::API_URL . '/games');

        if (! $response->ok()) {
            return [];
        }

        return array_map(fn (array $game) => $this->normalize($game), $response->json() ?? []);
    }

    public function findById(int $igdbId): ?array
    {
        $response = Http::withHeaders($this->headers())
            ->withBody(
                'fields name,cover.image_id,genres.name,first_release_date;'
                . ' where id = ' . $igdbId . ';',
                'text/plain'
            )
            ->post(self::API_URL . '/games');

        if (! $response->ok() || empty($response->json())) {
            return null;
        }

        return $this->normalize($response->json()[0]);
    }

    public function coverUrl(string $imageId, string $size = 'cover_big'): string
    {
        return "https://images.igdb.com/igdb/image/upload/t_{$size}/{$imageId}.jpg";
    }

    private function normalize(array $game): array
    {
        $imageId = $game['cover']['image_id'] ?? null;

        return [
            'igdb_id'      => $game['id'],
            'name'         => $game['name'],
            'cover_url'    => $imageId ? $this->coverUrl($imageId) : null,
            'image_id'     => $imageId,
            'genres'       => collect($game['genres'] ?? [])->pluck('name')->all(),
            'released_at'  => isset($game['first_release_date'])
                ? date('Y-m-d', $game['first_release_date'])
                : null,
        ];
    }

    private function headers(): array
    {
        return [
            'Client-ID'     => config('services.igdb.client_id'),
            'Authorization' => 'Bearer ' . $this->accessToken(),
        ];
    }

    private function accessToken(): string
    {
        return Cache::remember('igdb.access_token', now()->addDays(50), function () {
            $response = Http::post(self::TOKEN_URL, [
                'client_id'     => config('services.igdb.client_id'),
                'client_secret' => config('services.igdb.client_secret'),
                'grant_type'    => 'client_credentials',
            ]);

            return $response->json('access_token');
        });
    }
}
