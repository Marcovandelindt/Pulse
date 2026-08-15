<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Person;
use App\Models\TvSeries;
use App\Services\Tmdb\TMDBRecommendationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class RecommendationController extends Controller
{
    public function __construct(
        private readonly TMDBRecommendationService $tmdb,
    ) {}

    public function index(): View
    {
        $recommendations = $this->getSimilarRecommendations()
            ->unique('tmdb_id')
            ->sortByDesc('vote_average')
            ->values()
            ->take(24);

        return view('pages.recommendations.index', [
            'mode'            => 'general',
            'topActors'       => collect(),
            'recommendations' => $recommendations,
            'allPeople'       => $this->allPeople(),
            'person'          => null,
            'credits'         => collect(),
        ]);
    }

    public function byActor(Person $person): View
    {
        $credits       = $this->tmdb->getPersonCombinedCredits($person->tmdb_id);
        $watchedMovies = Movie::where('watch_count', '>', 0)->pluck('tmdb_id')->flip();
        $watchedSeries = TvSeries::whereNotNull('last_watched_at')->pluck('tmdb_id')->flip();

        $castCredits = collect($credits['cast'] ?? [])
            ->filter(fn (array $c): bool => !empty($c['poster_path']) && ($c['vote_count'] ?? 0) >= 20)
            ->unique('id')
            ->map(fn (array $c): array => [
                'tmdb_id'      => $c['id'],
                'media_type'   => $c['media_type'],
                'title'        => $c['title'] ?? $c['name'] ?? '—',
                'poster_url'   => $this->posterUrl($c['poster_path']),
                'year'         => substr($c['release_date'] ?? $c['first_air_date'] ?? '', 0, 4),
                'vote_average' => round($c['vote_average'] ?? 0, 1),
                'watched'      => $c['media_type'] === 'movie'
                    ? isset($watchedMovies[$c['id']])
                    : isset($watchedSeries[$c['id']]),
            ])
            ->sortByDesc('vote_average')
            ->values();

        return view('pages.recommendations.index', [
            'mode'            => 'actor',
            'topActors'       => $this->getTopActors(),
            'recommendations' => collect(),
            'allPeople'       => $this->allPeople(),
            'person'          => $person,
            'credits'         => $castCredits,
        ]);
    }

    private function getTopActors(int $limit = 10): Collection
    {
        $movieActors = DB::table('movie_person')
            ->join('movies', 'movie_person.movie_id', '=', 'movies.id')
            ->join('people', 'movie_person.person_id', '=', 'people.id')
            ->where('movie_person.department', 'Acting')
            ->where('movies.watch_count', '>', 0)
            ->selectRaw('people.id, people.name, people.tmdb_id, people.profile_path,
                COUNT(*) * 10 + SUM(CASE WHEN movie_person.cast_order <= 5 THEN 5 ELSE 0 END) as score')
            ->groupBy('people.id', 'people.name', 'people.tmdb_id', 'people.profile_path')
            ->get()
            ->keyBy('id');

        // Per series: keep only the single most-prominent actor (lowest cast_order)
        // so a long-running series like Modern Family contributes just one actor.
        $topPerSeries = DB::table('tv_series_person')
            ->join('tv_series', 'tv_series_person.tv_series_id', '=', 'tv_series.id')
            ->join('people', 'tv_series_person.person_id', '=', 'people.id')
            ->where('tv_series_person.department', 'Acting')
            ->whereNotNull('tv_series.last_watched_at')
            ->selectRaw('tv_series.id as series_id, people.id as person_id,
                people.name, people.tmdb_id, people.profile_path,
                MIN(tv_series_person.cast_order) as min_cast_order')
            ->groupBy('tv_series.id', 'people.id', 'people.name', 'people.tmdb_id', 'people.profile_path')
            ->get()
            ->groupBy('series_id')
            ->map(fn (Collection $actors): object => $actors->sortBy('min_cast_order')->first())
            ->values();

        // Aggregate per person: each series where they're the lead counts as 15 points.
        $tvActors = $topPerSeries
            ->groupBy('person_id')
            ->map(fn (Collection $entries): object => (object) [
                'id'           => $entries->first()->person_id,
                'name'         => $entries->first()->name,
                'tmdb_id'      => $entries->first()->tmdb_id,
                'profile_path' => $entries->first()->profile_path,
                'score'        => $entries->count() * 15,
            ])
            ->keyBy('id');

        $merged = $movieActors->map(function (object $actor) use ($tvActors): object {
            $actor->score += (float) ($tvActors->get($actor->id)?->score ?? 0);
            return $actor;
        });

        $tvActors->each(function (object $actor) use ($merged, $movieActors): void {
            if (! $movieActors->has($actor->id)) {
                $merged->put($actor->id, $actor);
            }
        });

        return $merged->sortByDesc('score')->take($limit)->values();
    }

    private function getActorRecommendations(Collection $actors): Collection
    {
        $knownMovies = Movie::pluck('tmdb_id')->flip();
        $knownSeries = TvSeries::pluck('tmdb_id')->flip();
        $results     = collect();

        foreach ($actors as $actor) {
            $credits = $this->tmdb->getPersonCombinedCredits($actor->tmdb_id);

            collect($credits['cast'] ?? [])
                ->filter(fn (array $c): bool =>
                    ! empty($c['poster_path']) &&
                    ($c['vote_count'] ?? 0) >= 50 &&
                    ($c['vote_average'] ?? 0) >= 5.5 &&
                    (
                        ($c['media_type'] === 'movie' && ! isset($knownMovies[$c['id']])) ||
                        ($c['media_type'] === 'tv'    && ! isset($knownSeries[$c['id']]))
                    )
                )
                ->sortByDesc('popularity')
                ->take(3)
                ->each(function (array $c) use (&$results, $actor): void {
                    $results->push([
                        'tmdb_id'      => $c['id'],
                        'media_type'   => $c['media_type'],
                        'title'        => $c['title'] ?? $c['name'] ?? '—',
                        'poster_url'   => $this->posterUrl($c['poster_path']),
                        'year'         => substr($c['release_date'] ?? $c['first_air_date'] ?? '', 0, 4),
                        'vote_average' => round($c['vote_average'] ?? 0, 1),
                        'because'      => $actor->name,
                        'because_type' => 'actor',
                    ]);
                });
        }

        return $results;
    }

    private function getSimilarRecommendations(): Collection
    {
        $knownMovies = Movie::pluck('tmdb_id')->flip();
        $knownSeries = TvSeries::pluck('tmdb_id')->flip();
        $results     = collect();

        $topMovies = Movie::where('watch_count', '>', 0)
            ->whereNotNull('vote_average')
            ->orderByDesc('vote_average')
            ->take(5)
            ->get();

        foreach ($topMovies as $movie) {
            $recs = $this->tmdb->getMovieRecommendations($movie->tmdb_id);
            collect($recs['results'] ?? [])
                ->filter(fn (array $r): bool => ! empty($r['poster_path']) && ! isset($knownMovies[$r['id']]))
                ->take(5)
                ->each(function (array $r) use (&$results, $movie): void {
                    $results->push([
                        'tmdb_id'      => $r['id'],
                        'media_type'   => 'movie',
                        'title'        => $r['title'] ?? '—',
                        'poster_url'   => $this->posterUrl($r['poster_path']),
                        'year'         => substr($r['release_date'] ?? '', 0, 4),
                        'vote_average' => round($r['vote_average'] ?? 0, 1),
                        'because'      => $movie->title,
                        'because_type' => 'similar',
                    ]);
                });
        }

        $topSeries = TvSeries::whereNotNull('last_watched_at')
            ->whereNotNull('vote_average')
            ->orderByDesc('vote_average')
            ->take(5)
            ->get();

        foreach ($topSeries as $series) {
            $recs = $this->tmdb->getTvRecommendations($series->tmdb_id);
            collect($recs['results'] ?? [])
                ->filter(fn (array $r): bool => ! empty($r['poster_path']) && ! isset($knownSeries[$r['id']]))
                ->take(5)
                ->each(function (array $r) use (&$results, $series): void {
                    $results->push([
                        'tmdb_id'      => $r['id'],
                        'media_type'   => 'tv',
                        'title'        => $r['name'] ?? '—',
                        'poster_url'   => $this->posterUrl($r['poster_path']),
                        'year'         => substr($r['first_air_date'] ?? '', 0, 4),
                        'vote_average' => round($r['vote_average'] ?? 0, 1),
                        'because'      => $series->name,
                        'because_type' => 'similar',
                    ]);
                });
        }

        return $results;
    }

    /** @return Collection<int, array{id: int, name: string, name_en: string|null, profile_url: string|null}> */
    private function allPeople(): Collection
    {
        return Person::orderBy('name')
            ->get(['id', 'name', 'name_en', 'profile_path'])
            ->map(fn (Person $p): array => [
                'id'          => $p->id,
                'name'        => $p->name,
                'name_en'     => $p->name_en,
                'profile_url' => $p->profile_path
                    ? config('tmdb.image_base_url').config('tmdb.profile_sizes.small').$p->profile_path
                    : null,
            ]);
    }

    private function posterUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return config('tmdb.image_base_url').config('tmdb.poster_sizes.medium').$path;
    }
}
