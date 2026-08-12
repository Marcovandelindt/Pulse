<?php

declare(strict_types=1);

namespace App\Actions\Media\Movies;

use App\Models\MovieWatch;

final class DeleteMovieWatch
{
    public function handle(MovieWatch $watch): void
    {
        $movie = $watch->movie;
        $watch->delete();

        $movie->watch_count = $movie->watches()->count();
        $movie->last_watched_at = $movie->watches()->latest('watched_at')->value('watched_at');
        $movie->save();
    }
}
