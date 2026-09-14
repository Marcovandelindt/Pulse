<?php

declare(strict_types=1);

namespace App\Actions\Nintendo;

use App\Models\NintendoGame;
use App\Services\Nintendo\NintendoService;
use Illuminate\Support\Carbon;

final class SyncNintendoData
{
    public function __construct(
        private readonly NintendoService $service,
    ) {}

    public function handle(): int
    {
        $summaries = $this->service->fetchDailySummaries();

        $synced = 0;

        foreach ($summaries as $entry) {
            $game = NintendoGame::firstOrCreate(
                ['application_id' => $entry['applicationId']],
                [
                    'name'      => $entry['applicationName'],
                    'image_url' => $entry['imageUrl'] ?? null,
                ],
            );

            $game->dailyRecords()->updateOrCreate(
                ['date' => $entry['date']],
                ['minutes_played' => $entry['playingTime']],
            );

            $synced++;
        }

        $this->recalculateTotals();

        return $synced;
    }

    private function recalculateTotals(): void
    {
        NintendoGame::query()->each(function (NintendoGame $game): void {
            $game->update([
                'total_minutes'   => $game->dailyRecords()->sum('minutes_played'),
                'first_played_at' => $game->dailyRecords()->min('date'),
                'last_played_at'  => $game->dailyRecords()->max('date'),
                'last_synced_at'  => Carbon::now(),
            ]);
        });
    }
}
