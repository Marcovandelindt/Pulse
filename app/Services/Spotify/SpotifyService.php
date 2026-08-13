<?php

declare(strict_types=1);

namespace App\Services\Spotify;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SpotifyService
{
    public function __construct(
        private readonly SpotifyAuthService $auth,
    ) {}

    public function getAuthenticatedClient(): ?array
    {
        $credentials = Setting::getSpotifyCredentials();

        if (! $credentials['refresh_token']) {
            return null;
        }

        if ($this->auth->isTokenExpired((int) $credentials['expires_at'])) {
            try {
                $fresh = $this->auth->refreshAccessToken($credentials['refresh_token']);
                Setting::set('spotify_access_token', $fresh['access_token']);
                Setting::set('spotify_refresh_token', $fresh['refresh_token']);
                Setting::set('spotify_token_expires_at', $fresh['expires_at']);

                return ['access_token' => $fresh['access_token']];
            } catch (RuntimeException $e) {
                if (str_contains($e->getMessage(), 'invalid_grant')) {
                    $this->disconnect();

                    return null;
                }

                throw $e;
            }
        }

        return ['access_token' => $credentials['access_token']];
    }

    public function isConnected(): bool
    {
        return (bool) Setting::get('spotify_refresh_token');
    }

    public function disconnect(): void
    {
        Setting::remove('spotify_id');
        Setting::remove('spotify_access_token');
        Setting::remove('spotify_refresh_token');
        Setting::remove('spotify_token_expires_at');
    }

    public function get(string $endpoint, array $params = []): ?array
    {
        $client = $this->getAuthenticatedClient();

        if ($client === null) {
            return null;
        }

        $response = Http::withToken($client['access_token'])
            ->get('https://api.spotify.com/v1'.$endpoint, $params);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function post(string $endpoint, array $data = []): ?array
    {
        $client = $this->getAuthenticatedClient();

        if ($client === null) {
            return null;
        }

        $response = Http::withToken($client['access_token'])
            ->post('https://api.spotify.com/v1'.$endpoint, $data);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }
}
