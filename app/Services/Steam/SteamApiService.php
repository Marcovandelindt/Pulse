<?php

declare(strict_types=1);

namespace App\Services\Steam;

use App\Models\SteamGame;
use Illuminate\Support\Facades\Http;

final class SteamApiService
{
    private const BASE_URL = 'https://api.steampowered.com';

    private string $apiKey;
    private string $steamId;

    public function __construct()
    {
        $this->apiKey = config('services.steam.api_key', '');
        $this->steamId = config('services.steam.steam_id', '');
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function setSteamId(string $steamId): self
    {
        $this->steamId = $steamId;

        return $this;
    }

    public function getOwnedGames(): array
    {
        $response = Http::get(self::BASE_URL.'/IPlayerService/GetOwnedGames/v1/', [
            'key'                   => $this->apiKey,
            'steamid'               => $this->steamId,
            'include_appinfo'       => 1,
            'include_played_free_games' => 1,
            'format'                => 'json',
        ]);

        return $response->json('response.games', []);
    }

    public function getRecentlyPlayedGames(): array
    {
        $response = Http::get(self::BASE_URL.'/IPlayerService/GetRecentlyPlayedGames/v1/', [
            'key'     => $this->apiKey,
            'steamid' => $this->steamId,
            'format'  => 'json',
        ]);

        return $response->json('response.games', []);
    }

    public function resolveVanityUrl(string $url): array
    {
        $vanityUrl = basename(rtrim($url, '/'));

        $response = Http::get(self::BASE_URL.'/ISteamUser/ResolveVanityURL/v1/', [
            'key'       => $this->apiKey,
            'vanityurl' => $vanityUrl,
            'format'    => 'json',
        ]);

        $data = $response->json('response', []);

        if (($data['success'] ?? 0) === 1) {
            return ['success' => true, 'steam_id' => $data['steamid']];
        }

        return ['success' => false, 'error' => $data['message'] ?? 'Could not resolve vanity URL.'];
    }

    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'API key is not configured.'];
        }

        if (empty($this->steamId)) {
            return ['success' => false, 'error' => 'Steam ID is not configured.'];
        }

        try {
            $games = $this->getOwnedGames();

            return ['success' => true, 'game_count' => count($games)];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncGames(): array
    {
        try {
            $games = $this->getOwnedGames();
            $recentGames = $this->getRecentlyPlayedGames();

            $recentMap = collect($recentGames)->keyBy('appid');

            foreach ($games as $game) {
                $appid = $game['appid'];
                $recent = $recentMap->get($appid);

                SteamGame::updateOrCreate(
                    ['steam_appid' => $appid],
                    [
                        'name'                   => $game['name'] ?? 'Unknown',
                        'image_url'              => isset($game['img_icon_url'])
                            ? "https://media.steampowered.com/steamcommunity/public/images/apps/{$appid}/{$game['img_icon_url']}.jpg"
                            : null,
                        'playtime_minutes'       => $game['playtime_forever'] ?? 0,
                        'playtime_2weeks_minutes' => $recent ? ($recent['playtime_2weeks'] ?? null) : null,
                        'last_played_at'         => isset($game['rtime_last_played']) && $game['rtime_last_played'] > 0
                            ? date('Y-m-d H:i:s', $game['rtime_last_played'])
                            : null,
                    ],
                );
            }

            return ['success' => true, 'game_count' => count($games)];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
