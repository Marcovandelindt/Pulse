<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationGame;
use App\Services\PlayStation\PsnApiTrophyDetailService;

final class FetchPlayStationTrophies
{
    public function __construct(
        private readonly PsnApiTrophyDetailService $apiService,
    ) {}

    public function handle(PlayStationGame $game): string
    {
        $count = $this->apiService->fetchAndStore($game);

        return "{$count} trophies geladen via PSN API inclusief earned status.";
    }
}
