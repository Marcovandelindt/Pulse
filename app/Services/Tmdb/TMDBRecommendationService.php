<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

use Illuminate\Support\Facades\Cache;

final class TMDBRecommendationService
{
    public function __construct(private readonly TMDBClient $client) {}

    public function getPersonCombinedCredits(int $tmdbId): array
    {
        return Cache::remember('tmdb_person_combined_'.$tmdbId, 3600, fn () =>
            $this->client->get('/person/'.$tmdbId.'/combined_credits')
        );
    }

    public function getMovieRecommendations(int $tmdbId): array
    {
        return Cache::remember('tmdb_movie_recs_'.$tmdbId, 3600, fn () =>
            $this->client->get('/movie/'.$tmdbId.'/recommendations', ['page' => 1])
        );
    }

    public function getTvRecommendations(int $tmdbId): array
    {
        return Cache::remember('tmdb_tv_recs_'.$tmdbId, 3600, fn () =>
            $this->client->get('/tv/'.$tmdbId.'/recommendations', ['page' => 1])
        );
    }
}
