<?php

declare(strict_types=1);

namespace App\Actions\Media\Movies;

use App\Models\Movie;
use App\Services\Tmdb\TMDBMovieService;

final class AddMovieFromTmdb
{
    public function __construct(
        private readonly TMDBMovieService $service,
    ) {}

    public function handle(int $tmdbId): Movie
    {
        return $this->service->createFromTMDB($tmdbId);
    }
}
