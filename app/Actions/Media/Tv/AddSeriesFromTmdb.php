<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\TvSeries;
use App\Services\Tmdb\TMDBTVService;

final class AddSeriesFromTmdb
{
    public function __construct(
        private readonly TMDBTVService $service,
    ) {}

    public function handle(int $tmdbId): TvSeries
    {
        $series = $this->service->createFromTMDB($tmdbId);

        for ($i = 1; $i <= $series->number_of_seasons; $i++) {
            $this->service->createSeasonFromTMDB($series, $i);
        }

        return $series->fresh(['seasons.episodes']);
    }
}
