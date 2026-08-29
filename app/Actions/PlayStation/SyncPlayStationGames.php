<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Services\PlayStation\PlayStationScraperService;

final class SyncPlayStationGames
{
    public function __construct(
        private readonly PlayStationScraperService $scraper,
    ) {}

    public function handle(): int
    {
        $username = config('services.playstation.username');
        $cookie   = config('services.playstation.cookie');

        if (! $username) {
            throw new \RuntimeException('PlayStation username not configured.');
        }

        if ($cookie) {
            $this->scraper->setSessionCookie((string) $cookie);
        }

        return $this->scraper->syncGames((string) $username);
    }
}
