<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationGame;
use App\Services\PlayStation\PsnApiTrophyDetailService;
use App\Services\PlayStation\PsnProfilesScraperService;

final class FetchPlayStationTrophies
{
    public function __construct(
        private readonly PsnApiTrophyDetailService $apiService,
        private readonly PsnProfilesScraperService $scraper,
    ) {}

    public function handle(PlayStationGame $game): string
    {
        if (config('services.playstation.trophy_source') === 'psnprofiles') {
            return $this->handlePsnProfiles($game);
        }

        $count = $this->apiService->fetchAndStore($game);

        return "{$count} trophies geladen via PSN API inclusief earned status.";
    }

    private function handlePsnProfiles(PlayStationGame $game): string
    {
        $result = $this->scraper->fetchAndStore($game);
        $count  = $result['count'];

        return $result['user_page']
            ? "{$count} trophies geladen inclusief earned status."
            : "{$count} trophies geladen (geen earned status — zoek je PSN-naam éénmalig op op psnprofiles.com om dat te activeren).";
    }
}
