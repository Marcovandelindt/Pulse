<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationGame;
use App\Services\PlayStation\PsnProfilesScraperService;

final class FetchPlayStationTrophies
{
    public function __construct(
        private readonly PsnProfilesScraperService $scraper,
    ) {}

    public function handle(PlayStationGame $game): string
    {
        $result = $this->scraper->fetchAndStore($game);
        $count  = $result['count'];

        return $result['user_page']
            ? "{$count} trophies geladen inclusief earned status."
            : "{$count} trophies geladen (geen earned status — zoek je PSN-naam éénmalig op op psnprofiles.com om dat te activeren).";
    }
}
