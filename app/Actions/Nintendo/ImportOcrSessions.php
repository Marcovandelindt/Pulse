<?php

declare(strict_types=1);

namespace App\Actions\Nintendo;

use App\Models\NintendoGame;
use Illuminate\Support\Str;

final class ImportOcrSessions
{
    /**
     * @param array<int, array{game: string, date: string, minutes: int}> $sessions
     */
    public function handle(array $sessions): int
    {
        $imported = 0;

        foreach ($sessions as $session) {
            if (empty($session['game']) || empty($session['date']) || (int) $session['minutes'] < 1) {
                continue;
            }

            $game = $this->resolveGame($session['game']);

            $game->dailyRecords()->updateOrCreate(
                ['date' => $session['date']],
                ['minutes_played' => (int) $session['minutes']],
            );

            $game->update([
                'total_minutes'   => $game->dailyRecords()->sum('minutes_played'),
                'first_played_at' => $game->dailyRecords()->min('date'),
                'last_played_at'  => $game->dailyRecords()->max('date'),
            ]);

            $imported++;
        }

        return $imported;
    }

    private function resolveGame(string $name): NintendoGame
    {
        // Exact match
        $game = NintendoGame::where('name', $name)->first();

        if (! $game && strlen($name) >= 10) {
            // Prefix match — handles names truncated by the Nintendo Store app
            $game = NintendoGame::where('name', 'like', $name . '%')->first();
        }

        return $game ?? NintendoGame::create([
            'name'           => $name,
            'application_id' => 'manual-' . Str::uuid(),
        ]);
    }
}
