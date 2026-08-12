<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\TvSeries;
use App\Services\Tmdb\TMDBTVService;

final class RefreshSeriesFromTmdb
{
    public function __construct(
        private readonly TMDBTVService $service,
    ) {}

    public function handle(TvSeries $series): void
    {
        $this->service->clearCache($series->tmdb_id);
        $updated = $this->service->createFromTMDB($series->tmdb_id);

        for ($i = 1; $i <= $updated->number_of_seasons; $i++) {
            $this->service->createSeasonFromTMDB($updated, $i);
        }
    }
}
