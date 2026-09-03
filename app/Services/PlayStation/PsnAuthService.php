<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PsnAuthService
{
    private const AUTH_BASE    = 'https://ca.account.sony.com/api/authz/v3/oauth';
    private const CLIENT_ID     = '09515159-7237-4370-9b40-3806e67c0891';
    private const CLIENT_SECRET = 'ucPjka5tntB2KqsP';
    private const REDIRECT_URI  = 'com.scee.psxandroid.scecompcall://redirect';
    private const SCOPE         = 'psn:mobile.v2.core psn:clientapp';

    public function getAccountId(): string
    {
        return Cache::rememberForever('psn_account_id', function () {
            $response = Http::withToken($this->getAccessToken())
                ->withHeaders(['User-Agent' => 'PlayStation/21090100 CFNetwork/1126 Darwin/19.5.0'])
                ->get('https://dms.api.playstation.com/api/v1/devices/accounts/me');

            if (! $response->successful()) {
                throw new RuntimeException('Failed to fetch PSN account ID: '.$response->body());
            }

            return (string) $response->json('accountId');
        });
    }

    public function getAccessToken(): string
    {
        return Cache::remember('psn_access_token', 3000, function () {
            $refreshToken = Setting::getPsnRefreshToken();

            if ($refreshToken) {
                return $this->refreshAccessToken($refreshToken);
            }

            return $this->exchangeNpsso();
        });
    }

    private function exchangeNpsso(): string
    {
        $npsso = config('services.playstation.npsso');

        if (! $npsso) {
            throw new RuntimeException('PSN_NPSSO is not configured in .env');
        }

        // Step 1: exchange NPSSO for authorization code.
        // Use RFC 3986 encoding (%20 for spaces, not +) to match Sony's expectations,
        // and append the query string manually to avoid Guzzle re-encoding the URL.
        $queryString = http_build_query([
            'access_type'   => 'offline',
            'client_id'     => self::CLIENT_ID,
            'redirect_uri'  => self::REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => self::SCOPE,
        ], '', '&', PHP_QUERY_RFC3986);

        $response = Http::withoutRedirecting()
            ->withHeaders([
                'Cookie'     => "npsso={$npsso}",
                'User-Agent' => 'com.scee.psxandroid.scearp/1.0',
                'Accept'     => 'application/json',
            ])
            ->get(self::AUTH_BASE.'/authorize?'.$queryString);

        $location = $response->header('Location');

        if (! $location || ! str_contains($location, 'code=')) {
            throw new RuntimeException(
                'Failed to get PSN authorization code. '.
                'Status: '.$response->status().' — '.$response->body().
                ' (NPSSO length: '.strlen((string) $npsso).')'
            );
        }

        parse_str(parse_url($location, PHP_URL_QUERY), $params);
        $code = $params['code'] ?? null;

        if (! $code) {
            throw new RuntimeException('PSN authorization code not found in redirect.');
        }

        // Step 2: exchange code for access + refresh tokens
        $tokenResponse = Http::withBasicAuth(self::CLIENT_ID, self::CLIENT_SECRET)
            ->withHeaders(['User-Agent' => 'com.scee.psxandroid.scearp/1.0'])
            ->asForm()
            ->post(self::AUTH_BASE.'/token', [
                'code'         => $code,
                'grant_type'   => 'authorization_code',
                'redirect_uri' => self::REDIRECT_URI,
                'token_format' => 'jwt',
            ]);

        if (! $tokenResponse->successful()) {
            throw new RuntimeException('Failed to exchange PSN code for tokens: '.$tokenResponse->body());
        }

        $data = $tokenResponse->json();

        Setting::storePsnRefreshToken($data['refresh_token']);

        return $data['access_token'];
    }

    private function refreshAccessToken(string $refreshToken): string
    {
        $response = Http::withoutVerifying()
            ->withBasicAuth(self::CLIENT_ID, self::CLIENT_SECRET)
            ->asForm()
            ->post(self::AUTH_BASE.'/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope'         => self::SCOPE,
                'token_format'  => 'jwt',
            ]);

        if (! $response->successful()) {
            // Refresh token expired — clear it and re-exchange NPSSO
            Setting::clearPsnRefreshToken();
            Cache::forget('psn_access_token');

            return $this->exchangeNpsso();
        }

        $data = $response->json();

        Setting::storePsnRefreshToken($data['refresh_token']);

        return $data['access_token'];
    }
}
