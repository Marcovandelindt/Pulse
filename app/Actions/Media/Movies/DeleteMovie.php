<?php

declare(strict_types=1);

namespace App\Actions\Media\Movies;

use App\Models\Movie;

final class DeleteMovie
{
    public function handle(Movie $movie): void
    {
        $movie->delete();
    }
}
