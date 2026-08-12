<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

use App\Models\Movie;
use Illuminate\Support\Facades\Cache;

final class TMDBMovieService
{
    public function __construct(
        private readonly TMDBClient $client,
        private readonly PersonSyncService $personSyncService,
    ) {}

    public function search(string $query, int $page = 1): array
    {
        $cacheKey = 'tmdb_movie_search_'.md5($query).'_'.$page;

        return Cache::remember($cacheKey, config('tmdb.cache_duration') * 60, fn () => $this->client->get('/search/movie', ['query' => $query, 'page' => $page])
        );
    }

    public function getDetails(int $tmdbId): array
    {
        $cacheKey = 'tmdb_movie_details_'.$tmdbId;

        return Cache::remember($cacheKey, config('tmdb.cache_duration') * 60, fn () => $this->client->get('/movie/'.$tmdbId, ['append_to_response' => 'credits'])
        );
    }

    public function createFromTMDB(int $tmdbId): Movie
    {
        $data = $this->getDetails($tmdbId);
        $credits = $data['credits'] ?? [];

        $movie = Movie::updateOrCreate(
            ['tmdb_id' => $tmdbId],
            [
                'title' => $data['title'] ?? '',
                'original_title' => $data['original_title'] ?? '',
                'overview' => $data['overview'] ?? null,
                'poster_path' => $data['poster_path'] ?? null,
                'backdrop_path' => $data['backdrop_path'] ?? null,
                'release_date' => ! empty($data['release_date']) ? $data['release_date'] : null,
                'runtime' => $data['runtime'] ?? null,
                'vote_average' => $data['vote_average'] ?? null,
                'genres' => array_column($data['genres'] ?? [], 'name'),
                'original_language' => $data['original_language'] ?? null,
            ],
        );

        if (! empty($credits)) {
            $this->personSyncService->syncMovieCast($movie, $credits);
        }

        return $movie;
    }
}
