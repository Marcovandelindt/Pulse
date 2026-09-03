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

    /**
     * Sync the active gaming presence and return the current active record (or null).
     * Accepts a pre-fetched $currentGame to avoid a second PSN API call when the
     * dashboard has already retrieved it.
     *
     * @param  array<string, mixed>|null  $currentGame
     */
    public function handle(?array $currentGame = null): ?GamingPresence
    {
        $currentGame ??= $this->presenceService->getCurrentGame();

        $activePresence = GamingPresence::where('platform', 'playstation')
            ->active()
            ->latest('started_at')
            ->first();

        if ($currentGame === null) {
            $activePresence?->update(['ended_at' => now(), 'last_seen_at' => now()]);

            return null;
        }

        if ($activePresence === null) {
            return $this->openSession($currentGame);
        }

        $isStale = $activePresence->last_seen_at->diffInMinutes(now()) > 10;

        if ($activePresence->game_name !== $currentGame['title'] || $isStale) {
            $activePresence->update(['ended_at' => now(), 'last_seen_at' => now()]);

            return $this->openSession($currentGame);
        }

        $activePresence->update(['last_seen_at' => now()]);

        return $activePresence;
    }

    /** @param array<string, mixed> $game */
    private function openSession(array $game): GamingPresence
    {
        $psGame = PlayStationGame::where('name', $game['title'])
            ->orWhere('display_name', $game['title'])
            ->first();

        return GamingPresence::create([
            'platform'     => 'playstation',
            'game_id'      => $psGame?->id,
            'game_name'    => $game['title'],
            'image_url'    => $game['image_url'],
            'started_at'   => now(),
            'last_seen_at' => now(),
        ]);
    }
}
