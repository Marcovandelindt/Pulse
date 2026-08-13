<?php

declare(strict_types=1);

namespace App\Services\Spotify;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SpotifyAuthService
{
    public function getAuthorizeUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        session(['spotify_oauth_state' => $state]);

        $scopes = implode(' ', config('spotify.default_scopes'));

        $query = http_build_query([
            'client_id' => config('spotify.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('spotify.redirect_uri'),
            'scope' => $scopes,
            'state' => $state,
        ]);

        return 'https://accounts.spotify.com/authorize?'.$query;
    }

    public function handleCallback(string $code, string $state): array
    {
        if ($state !== session('spotify_oauth_state')) {
            throw new RuntimeException('OAuth state mismatch.');
        }

        $tokenResponse = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode(config('spotify.client_id').':'.config('spotify.client_secret')),
        ])->asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('spotify.redirect_uri'),
        ])->json();

        if (empty($tokenResponse['access_token'])) {
            throw new RuntimeException('Failed to obtain Spotify access token: '.($tokenResponse['error'] ?? 'unknown error'));
        }

        $meResponse = Http::withToken($tokenResponse['access_token'])
            ->get('https://api.spotify.com/v1/me')
            ->json();

        return [
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'],
            'expires_at' => now()->addSeconds($tokenResponse['expires_in'])->timestamp,
            'spotify_id' => $meResponse['id'],
        ];
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode(config('spotify.client_id').':'.config('spotify.client_secret')),
        ])->asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ])->json();

        if (isset($response['error'])) {
            throw new RuntimeException('Failed to refresh Spotify token: '.$response['error']);
        }

        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? $refreshToken,
            'expires_at' => now()->addSeconds($response['expires_in'])->timestamp,
        ];
    }

    public function isTokenExpired(int $expiresAt): bool
    {
        return now()->timestamp >= $expiresAt - 300;
    }
}
