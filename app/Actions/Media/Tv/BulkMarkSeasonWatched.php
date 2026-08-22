<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\TvSeason;
use Carbon\Carbon;

final class BulkMarkSeasonWatched
{
    public function handle(TvSeason $season, ?Carbon $watchedAt, bool $yearOnly): int
    {
        $season->load('episodes');

        $count = 0;
        foreach ($season->episodes as $episode) {
            $episode->watches()->create([
                'watched_at' => $watchedAt,
                'year_only'  => $yearOnly,
            ]);
            $count++;
        }

        $season->updateProgress();
        $season->loadMissing('series');
        $season->series->recordWatch();

        return $count;
    }
}
