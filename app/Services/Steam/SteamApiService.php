<?php

declare(strict_types=1);

namespace App\Services\Steam;

use App\Models\SteamAccount;
use App\Models\SteamGame;
use Illuminate\Support\Facades\Http;

final class SteamApiService
{
    private const BASE_URL = 'https://api.steampowered.com';

    public function getOwnedGames(SteamAccount $account): array
    {
        $response = Http::get(self::BASE_URL.'/IPlayerService/GetOwnedGames/v1/', [
            'key'                       => $account->api_key,
            'steamid'                   => $account->steam_id,
            'include_appinfo'           => 1,
            'include_played_free_games' => 1,
            'format'                    => 'json',
        ]);

        return $response->json('response.games', []);
    }

    public function getRecentlyPlayedGames(SteamAccount $account): array
    {
        $response = Http::get(self::BASE_URL.'/IPlayerService/GetRecentlyPlayedGames/v1/', [
            'key'     => $account->api_key,
            'steamid' => $account->steam_id,
            'format'  => 'json',
        ]);

        return $response->json('response.games', []);
    }

    public function resolveVanityUrl(SteamAccount $account, string $url): array
    {
        $vanityUrl = basename(rtrim($url, '/'));

        $response = Http::get(self::BASE_URL.'/ISteamUser/ResolveVanityURL/v1/', [
            'key'       => $account->api_key,
            'vanityurl' => $vanityUrl,
            'format'    => 'json',
        ]);

        $data = $response->json('response', []);

        if (($data['success'] ?? 0) === 1) {
            return ['success' => true, 'steam_id' => $data['steamid']];
        }

        return ['success' => false, 'error' => $data['message'] ?? 'Could not resolve vanity URL.'];
    }

    public function testConnection(SteamAccount $account): array
    {
        try {
            $games = $this->getOwnedGames($account);

            return ['success' => true, 'game_count' => count($games)];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncGames(SteamAccount $account): array
    {
        try {
            $games = $this->getOwnedGames($account);
            $recentGames = $this->getRecentlyPlayedGames($account);

            $recentMap = collect($recentGames)->keyBy('appid');

            foreach ($games as $game) {
                $appid = $game['appid'];
                $recent = $recentMap->get($appid);

                SteamGame::updateOrCreate(
                    ['steam_account_id' => $account->id, 'steam_appid' => $appid],
                    [
                        'name'                    => $game['name'] ?? 'Unknown',
                        'image_url'               => isset($game['img_icon_url'])
                            ? "https://media.steampowered.com/steamcommunity/public/images/apps/{$appid}/{$game['img_icon_url']}.jpg"
                            : null,
                        'playtime_minutes'        => $game['playtime_forever'] ?? 0,
                        'playtime_2weeks_minutes' => $recent ? ($recent['playtime_2weeks'] ?? null) : null,
                        'last_played_at'          => isset($game['rtime_last_played']) && $game['rtime_last_played'] > 0
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
