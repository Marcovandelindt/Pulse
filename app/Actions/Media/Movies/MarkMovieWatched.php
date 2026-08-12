<?php

declare(strict_types=1);

namespace App\Actions\Media\Movies;

use App\Models\Movie;
use App\Models\MovieWatch;

final class MarkMovieWatched
{
    public function handle(Movie $movie, MovieWatchData $data): MovieWatch
    {
        $watch = $movie->watches()->create([
            'watched_at' => $data->watchedAt,
            'year_only' => $data->yearOnly,
            'notes' => $data->notes,
            'rating' => $data->rating,
        ]);

        $movie->incrementWatchCount();

        return $watch;
    }
}
