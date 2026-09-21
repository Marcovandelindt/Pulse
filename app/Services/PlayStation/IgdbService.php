<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class IgdbService
{
    private const TOKEN_URL = 'https://id.twitch.tv/oauth2/token';
    private const API_BASE  = 'https://api.igdb.com/v4';

    /** @return list<string> */
    public function getGenresForGame(string $gameName): array
    {
        return Cache::remember(
            'igdb_genres_'.md5($gameName),
            now()->addDays(30),
            fn () => $this->fetchGenres($gameName),
        );
    }

    /** @return list<string> */
    private function fetchGenres(string $gameName): array
    {
        $token    = $this->getAccessToken();
        $clientId = (string) config('services.igdb.client_id');
        $safe     = addslashes($gameName);

        $response = Http::withHeaders([
            'Client-ID'     => $clientId,
            'Authorization' => "Bearer {$token}",
        ])->withBody(
            "search \"{$safe}\"; fields name,genres.name; limit 5;",
            'text/plain',
        )->post(self::API_BASE.'/games');

        if (! $response->successful()) {
            throw new RuntimeException('IGDB API error ('.$response->status().'): '.$response->body());
        }

        $games = $response->json();

        if (empty($games) || ! is_array($games)) {
            return [];
        }

        $genres = $games[0]['genres'] ?? [];

        return array_values(array_map(fn (array $g) => (string) $g['name'], $genres));
    }

    private function getAccessToken(): string
    {
        return Cache::remember('igdb_access_token', now()->addDays(50), function () {
            $response = Http::post(self::TOKEN_URL, [
                'client_id'     => config('services.igdb.client_id'),
                'client_secret' => config('services.igdb.client_secret'),
                'grant_type'    => 'client_credentials',
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Failed to get IGDB access token: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }
}
