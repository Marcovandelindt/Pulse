<?php

declare(strict_types=1);

namespace App\Services\Spotify;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class SpotifyClient
{
    private const string TOKEN_CACHE_KEY = 'spotify.access_token';

    private const int    TOKEN_TTL = 3500;

    private const string BASE_URL = 'https://api.spotify.com/v1/';

    private const string TOKEN_URL = 'https://accounts.spotify.com/api/token';

    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_TTL, function () {
            $clientId = config('spotify.client_id');
            $clientSecret = config('spotify.client_secret');
            $credentials = base64_encode("{$clientId}:{$clientSecret}");

            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'client_credentials',
            ]);

            return $response->json('access_token', '');
        });
    }

    public function get(string $path, array $params = []): array
    {
        $response = Http::withToken($this->accessToken())
            ->get(self::BASE_URL.$path, $params);

        return $response->json() ?? [];
    }

    public function post(string $path, array $data): array
    {
        $response = Http::withToken($this->accessToken())
            ->post(self::BASE_URL.$path, $data);

        return $response->json() ?? [];
    }
}
