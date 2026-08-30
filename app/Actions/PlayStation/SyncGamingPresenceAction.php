<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\GamingPresence;
use App\Models\PlayStationGame;
use App\Services\PlayStation\PsnPresenceService;

final class SyncGamingPresenceAction
{
    public function __construct(
        private readonly PsnPresenceService $presenceService,
    ) {}

    public function handle(): void
    {
        $currentGame = $this->presenceService->getCurrentGame();

        $activePresence = GamingPresence::where('platform', 'playstation')
            ->active()
            ->latest('started_at')
            ->first();

        if ($currentGame === null) {
            $activePresence?->update(['ended_at' => now(), 'last_seen_at' => now()]);

            return;
        }

        if ($activePresence === null) {
            $this->openSession($currentGame);

            return;
        }

        if ($activePresence->game_name !== $currentGame['title']) {
            $activePresence->update(['ended_at' => now(), 'last_seen_at' => now()]);
            $this->openSession($currentGame);

            return;
        }

        $activePresence->update(['last_seen_at' => now()]);
    }

    /** @param array<string, mixed> $game */
    private function openSession(array $game): void
    {
        $psGame = PlayStationGame::where('name', $game['title'])
            ->orWhere('display_name', $game['title'])
            ->first();

        GamingPresence::create([
            'platform'     => 'playstation',
            'game_id'      => $psGame?->id,
            'game_name'    => $game['title'],
            'image_url'    => $game['image_url'],
            'started_at'   => now(),
            'last_seen_at' => now(),
        ]);
    }
}
