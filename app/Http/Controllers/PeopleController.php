<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PeopleController extends Controller
{
    public function index(): View
    {
        $people = Person::query()
            ->withCount([
                'movies'   => fn ($q) => $q->where('movie_person.department', 'Acting'),
                'tvSeries' => fn ($q) => $q->where('tv_series_person.department', 'Acting'),
            ])
            ->addSelect(DB::raw("(
                SELECT COALESCE(SUM(
                    (CASE WHEN m.runtime IS NOT NULL THEN m.runtime / 60.0 ELSE 1.0 END)
                    * COALESCE(m.watch_count, 1)
                    * CASE WHEN mp.cast_order IS NOT NULL AND mp.cast_order <= 2 THEN 1.5 ELSE 1.0 END
                ), 0)
                FROM movie_person mp
                INNER JOIN movies m ON m.id = mp.movie_id
                WHERE mp.person_id = people.id AND mp.department = 'Acting'
            ) + (
                SELECT COALESCE(SUM(
                    (CASE
                        WHEN tsp.episode_count IS NOT NULL AND ts.number_of_episodes > 0
                        THEN tsp.episode_count / ts.number_of_episodes
                        ELSE 1.0
                    END)
                    * (ts.watched_runtime_minutes / 60.0)
                    * CASE WHEN tsp.cast_order IS NOT NULL AND tsp.cast_order <= 3 THEN 1.5 ELSE 1.0 END
                ), 0)
                FROM tv_series_person tsp
                INNER JOIN tv_series ts ON ts.id = tsp.tv_series_id
                WHERE tsp.person_id = people.id AND tsp.department = 'Acting'
            ) as prominence_score"))
            ->where(function ($q) {
                $q->whereHas('movies', fn ($q) => $q->where('movie_person.department', 'Acting'))
                  ->orWhereHas('tvSeries', fn ($q) => $q->where('tv_series_person.department', 'Acting'));
            })
            ->orderByDesc('prominence_score')
            ->paginate(25);

        return view('pages.people.index', compact('people'));
    }

    public function show(Person $person): View
    {
        $movies = $person->movies()->with('watches')->get();
        $tvSeries = $person->tvSeries()->with('seasons.episodes.watches')->get();

        return view('pages.people.show', [
            'person' => $person,
            'movies' => $movies,
            'tvSeries' => $tvSeries,
            'totalMovieWatches' => $movies->sum(fn ($m) => $m->watches->count()),
            'totalEpisodesWatched' => $tvSeries->sum('episodes_watched'),
            'firstSeen' => $this->firstSeen($movies, $tvSeries),
            'lastSeen' => $this->lastSeen($movies, $tvSeries),
        ]);
    }

    private function firstSeen(Collection $movies, Collection $tvSeries): ?Carbon
    {
        return $this->allWatchDates($movies, $tvSeries)->sort()->first();
    }

    private function lastSeen(Collection $movies, Collection $tvSeries): ?Carbon
    {
        return $this->allWatchDates($movies, $tvSeries)->sort()->last();
    }

    private function allWatchDates(Collection $movies, Collection $tvSeries): Collection
    {
        $movieDates = $movies->flatMap(fn ($m) => $m->watches)->pluck('watched_at')->filter();

        $tvDates = $tvSeries
            ->flatMap(fn ($s) => $s->seasons)
            ->flatMap(fn ($se) => $se->episodes)
            ->flatMap(fn ($ep) => $ep->watches)
            ->pluck('watched_at')
            ->filter();

        return $movieDates->merge($tvDates);
    }
}
