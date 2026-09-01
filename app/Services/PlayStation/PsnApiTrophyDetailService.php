<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use App\Models\PlayStationGame;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PsnApiTrophyDetailService
{
    private const TROPHY_BASE = 'https://m.np.playstation.com/api/trophy/v1';
    private const USER_AGENT  = 'PlayStation/23.4.0 CFNetwork/1410.0.3 Darwin/22.6.0';

    public function __construct(
        private readonly PsnAuthService $auth,
        private readonly PsnTrophyService $trophyService,
    ) {}

    public function fetchAndStore(PlayStationGame $game): int
    {
        if (! $game->np_communication_id) {
            $this->trophyService->fetchAndStoreTrophies($game);
            $game->refresh();
        }

        if (! $game->np_communication_id) {
            throw new RuntimeException(
                "Could not find \"{$game->name}\" in your PSN trophy list. ".
                'The game may not have trophies or may not be played on this account.'
            );
        }

        $definitions = $this->fetchDefinitions($game);
        $earnedMap   = collect($this->fetchEarned($game))->keyBy('trophyId');
        $completedAt = null;

        foreach ($definitions as $def) {
            $trophyId    = $def['trophyId'];
            $earnedEntry = $earnedMap->get($trophyId);
            $isEarned    = (bool) ($earnedEntry['earned'] ?? false);
            $earnedAt    = isset($earnedEntry['earnedDateTime'])
                ? Carbon::parse($earnedEntry['earnedDateTime'])
                : null;

            $game->trophyList()->updateOrCreate(
                [
                    'trophy_id'       => $trophyId,
                    'trophy_group_id' => $def['trophyGroupId'] ?? 'default',
                ],
                [
                    'name'        => $def['trophyName'] ?? '',
                    'detail'      => $def['trophyDetail'] ?? null,
                    'icon_url'    => $def['trophyIconUrl'] ?? null,
                    'type'        => strtolower($def['trophyType'] ?? 'bronze'),
                    'is_earned'   => $isEarned,
                    'earned_at'   => $earnedAt,
                    'rarity'      => $earnedEntry['trophyRare'] ?? null,
                    'earned_rate' => isset($earnedEntry['trophyEarnedRate'])
                        ? (float) $earnedEntry['trophyEarnedRate']
                        : null,
                ]
            );

            if ($isEarned && strtolower($def['trophyType'] ?? '') === 'platinum') {
                $completedAt = $earnedAt;
            }
        }

        $game->completed_at = $completedAt;
        $game->save();

        return count($definitions);
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchDefinitions(PlayStationGame $game): array
    {
        $response = Http::withToken($this->auth->getAccessToken())
            ->withHeaders([
                'Accept'          => 'application/json',
                'Accept-Language' => 'en-US',
                'User-Agent'      => self::USER_AGENT,
            ])
            ->get(
                self::TROPHY_BASE."/npCommunicationIds/{$game->np_communication_id}/trophyGroups/all/trophies",
                $this->serviceNameParam($game),
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                "PSN API returned HTTP {$response->status()} fetching trophy definitions for \"{$game->name}\"."
            );
        }

        return $response->json('trophies', []);
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchEarned(PlayStationGame $game): array
    {
        $response = Http::withToken($this->auth->getAccessToken())
            ->withHeaders([
                'Accept'          => 'application/json',
                'Accept-Language' => 'en-US',
                'User-Agent'      => self::USER_AGENT,
            ])
            ->get(
                self::TROPHY_BASE."/users/me/npCommunicationIds/{$game->np_communication_id}/trophyGroups/all/trophies",
                $this->serviceNameParam($game),
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                "PSN API returned HTTP {$response->status()} fetching earned trophies for \"{$game->name}\"."
            );
        }

        return $response->json('trophies', []);
    }

    /** @return array<string, string> */
    private function serviceNameParam(PlayStationGame $game): array
    {
        // PS3/PS4/PSVITA games use the legacy 'trophy' service; PS5 uses 'trophy2' (default, no param needed)
        return $game->np_service_name === 'trophy'
            ? ['npServiceName' => 'trophy']
            : [];
    }
}
