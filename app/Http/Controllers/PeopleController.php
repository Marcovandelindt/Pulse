<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class PeopleController extends Controller
{
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
            'totalHours' => $this->calculateTotalHours($movies, $tvSeries),
            'firstSeen' => $this->firstSeen($movies, $tvSeries),
            'lastSeen' => $this->lastSeen($movies, $tvSeries),
        ]);
    }

    private function calculateTotalHours(Collection $movies, Collection $tvSeries): float
    {
        $movieMinutes = $movies->sum(fn ($m) => $m->watches->count() * ($m->runtime ?? 0));

        $tvMinutes = $tvSeries->sum(
            fn ($s) => $s->seasons->sum(
                fn ($season) => $season->episodes->sum(
                    fn ($ep) => $ep->watches->count() * ($ep->runtime ?? 0),
                ),
            ),
        );

        return round(($movieMinutes + $tvMinutes) / 60, 1);
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
