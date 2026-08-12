<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\TvSeries;
use Carbon\Carbon;

final class BulkMarkSeriesWatched
{
    public function handle(TvSeries $series, ?Carbon $watchedAt, bool $yearOnly): void
    {
        $series->load('seasons.episodes.watches');

        foreach ($series->seasons as $season) {
            foreach ($season->episodes as $episode) {
                if ($episode->watches->isEmpty()) {
                    $episode->watches()->create([
                        'watched_at' => $watchedAt,
                        'year_only' => $yearOnly,
                    ]);
                }
            }

            $season->updateProgress();
        }

        $series->recordWatch();
    }
}
