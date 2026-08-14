<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\TvEpisode;
use Carbon\Carbon;

final class BulkMarkUpToEpisode
{
    public function handle(TvEpisode $targetEpisode, ?Carbon $watchedAt, bool $yearOnly): int
    {
        $targetEpisode->load('season.series');
        $series = $targetEpisode->season->series;
        $series->load('seasons.episodes');

        $count = 0;
        $done = false;

        foreach ($series->seasons->sortBy('season_number') as $season) {
            if ($season->season_number === 0) {
                continue;
            }

            foreach ($season->episodes->sortBy('episode_number') as $episode) {
                $episode->watches()->create([
                    'watched_at' => $watchedAt,
                    'year_only' => $yearOnly,
                ]);
                $count++;

                if ($episode->id === $targetEpisode->id) {
                    $done = true;
                    break;
                }
            }

            $season->updateProgress();

            if ($done) {
                break;
            }
        }

        $series->recordWatch();

        return $count;
    }
}
