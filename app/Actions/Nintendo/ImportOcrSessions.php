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

            $game = NintendoGame::firstOrCreate(
                ['name' => $session['game']],
                ['application_id' => 'manual-' . Str::uuid()],
            );

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
}
