<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

use App\Models\TvEpisode;
use App\Models\TvSeason;
use App\Models\TvSeries;
use Illuminate\Support\Facades\Cache;

final class TMDBTVService
{
    public function __construct(
        private readonly TMDBClient $client,
        private readonly PersonSyncService $personSyncService,
    ) {}

    public function search(string $query, int $page = 1): array
    {
        $cacheKey = 'tmdb_tv_search_'.md5($query).'_'.$page;

        return Cache::remember($cacheKey, config('tmdb.cache_duration') * 60, fn () => $this->client->get('/search/tv', ['query' => $query, 'page' => $page])
        );
    }

    public function getDetails(int $tmdbId): array
    {
        $cacheKey = 'tmdb_tv_details_'.$tmdbId;

        return Cache::remember($cacheKey, config('tmdb.cache_duration') * 60, fn () => $this->client->get('/tv/'.$tmdbId)
        );
    }

    public function getAggregateCredits(int $tmdbId): array
    {
        $cacheKey = 'tmdb_tv_aggregate_credits_'.$tmdbId;

        return Cache::remember($cacheKey, config('tmdb.cache_duration') * 60, fn () => $this->client->get('/tv/'.$tmdbId.'/aggregate_credits')
        );
    }

    public function getEnglishName(int $tmdbId): ?string
    {
        $cacheKey = 'tmdb_tv_details_en_'.$tmdbId;

        $data = Cache::remember($cacheKey, config('tmdb.cache_duration') * 60, fn () => $this->client->getWithLanguage('/tv/'.$tmdbId, 'en-US')
        );

        return $data['name'] ?? null;
    }

    public function getSeasonDetails(int $seriesTmdbId, int $seasonNumber): array
    {
        $cacheKey = 'tmdb_tv_season_'.$seriesTmdbId.'_'.$seasonNumber;

        return Cache::remember($cacheKey, config('tmdb.cache_duration') * 60, fn () => $this->client->get('/tv/'.$seriesTmdbId.'/season/'.$seasonNumber)
        );
    }

    public function createFromTMDB(int $tmdbId): TvSeries
    {
        $data = $this->getDetails($tmdbId);
        $aggregateCredits = $this->getAggregateCredits($tmdbId);
        $englishName = $this->getEnglishName($tmdbId);

        $series = TvSeries::updateOrCreate(
            ['tmdb_id' => $tmdbId],
            [
                'name' => $data['name'] ?? '',
                'name_en' => ($englishName !== ($data['name'] ?? null)) ? $englishName : null,
                'original_name' => $data['original_name'] ?? '',
                'overview' => $data['overview'] ?? null,
                'poster_path' => $data['poster_path'] ?? null,
                'backdrop_path' => $data['backdrop_path'] ?? null,
                'first_air_date' => ! empty($data['first_air_date']) ? $data['first_air_date'] : null,
                'last_air_date' => ! empty($data['last_air_date']) ? $data['last_air_date'] : null,
                'vote_average' => $data['vote_average'] ?? null,
                'genres' => array_column($data['genres'] ?? [], 'name'),
                'status' => $data['status'] ?? null,
                'original_language' => $data['original_language'] ?? null,
                'number_of_seasons' => $data['number_of_seasons'] ?? 0,
                'number_of_episodes' => $data['number_of_episodes'] ?? 0,
            ],
        );

        if (! empty($aggregateCredits)) {
            $this->personSyncService->syncTvCast($series, $aggregateCredits);
        }

        return $series;
    }

    public function createSeasonFromTMDB(TvSeries $series, int $seasonNumber): TvSeason
    {
        $data = $this->getSeasonDetails($series->tmdb_id, $seasonNumber);

        $season = TvSeason::updateOrCreate(
            ['tv_series_id' => $series->id, 'season_number' => $seasonNumber],
            [
                'tmdb_id' => $data['id'] ?? 0,
                'name' => $data['name'] ?? 'Season '.$seasonNumber,
                'overview' => $data['overview'] ?? null,
                'poster_path' => $data['poster_path'] ?? null,
                'air_date' => ! empty($data['air_date']) ? $data['air_date'] : null,
                'episode_count' => count($data['episodes'] ?? []),
            ],
        );

        foreach ($data['episodes'] ?? [] as $ep) {
            TvEpisode::updateOrCreate(
                ['tv_season_id' => $season->id, 'episode_number' => $ep['episode_number']],
                [
                    'tmdb_id' => $ep['id'],
                    'name' => $ep['name'] ?? '',
                    'overview' => $ep['overview'] ?? null,
                    'air_date' => ! empty($ep['air_date']) ? $ep['air_date'] : null,
                    'runtime' => $ep['runtime'] ?? null,
                    'vote_average' => $ep['vote_average'] ?? null,
                ],
            );
        }

        return $season;
    }

    public function clearCache(int $tmdbId): void
    {
        Cache::forget('tmdb_tv_details_'.$tmdbId);
        Cache::forget('tmdb_tv_aggregate_credits_'.$tmdbId);
        Cache::forget('tmdb_tv_details_en_'.$tmdbId);

        for ($i = 0; $i <= 50; $i++) {
            Cache::forget('tmdb_tv_season_'.$tmdbId.'_'.$i);
        }
    }
}
