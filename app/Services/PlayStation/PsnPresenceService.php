<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class PsnPresenceService
{
    private const PRESENCE_URL = 'https://m.np.playstation.com/api/userProfile/v1/internal/users/%s/basicPresences';

    public function __construct(
        private readonly PsnAuthService $auth,
    ) {}

    public function getCurrentGame(): ?array
    {
        // Fetch presence from API, cached for 60 seconds
        $gameData = Cache::remember('psn_current_game', 60, function () {
            $accountId = $this->auth->getAccountId();

            $response = Http::withToken($this->auth->getAccessToken())
                ->withHeaders([
                    'User-Agent'      => 'PlayStation/21090100 CFNetwork/1126 Darwin/19.5.0',
                    'Accept-Language' => 'en-US',
                ])
                ->get(sprintf(self::PRESENCE_URL, $accountId), ['type' => 'primary']);

            if (! $response->successful()) {
                return null;
            }

            $presence = $response->json('basicPresence');

            if (! $presence || ($presence['primaryPlatformInfo']['onlineStatus'] ?? '') !== 'online') {
                return null;
            }

            $game = $presence['gameTitleInfoList'][0] ?? null;

            if (! $game || empty($game['titleName'])) {
                return null;
            }

            return [
                'title'     => $game['titleName'],
                'platform'  => $game['launchPlatform'] ?? $game['format'] ?? null,
                'image_url' => $game['conceptIconUrl'] ?? null,
            ];
        });

        return $gameData;
    }
}
