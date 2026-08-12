<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Movies;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\MovieWatch;
use Carbon\Carbon;
use Illuminate\View\View;

final class MovieStatsController extends Controller
{
    public function index(): View
    {
        $totalMovies = Movie::count();
        $totalWatches = MovieWatch::count();
        $totalMinutes = Movie::join('movie_watches', 'movies.id', '=', 'movie_watches.movie_id')
            ->sum('movies.runtime');
        $totalHours = round($totalMinutes / 60, 1);
        $averageRating = MovieWatch::whereNotNull('rating')->avg('rating');

        $uniqueMovies = Movie::whereHas('watches')->count();

        $firstWatch = MovieWatch::with('movie')->whereNotNull('watched_at')->oldest('watched_at')->first();
        $lastWatch = MovieWatch::with('movie')->whereNotNull('watched_at')->latest('watched_at')->first();

        $mostWatched = Movie::withCount('watches')
            ->orderByDesc('watches_count')
            ->limit(10)
            ->get();

        $recentByDay = $this->recentByDay();
        $genreStats = $this->genreBreakdown();

        return view('pages.movies.stats', compact(
            'totalMovies', 'totalWatches', 'totalHours', 'averageRating',
            'uniqueMovies', 'firstWatch', 'lastWatch', 'mostWatched', 'recentByDay', 'genreStats',
        ));
    }

    private function recentByDay(): array
    {
        return MovieWatch::with('movie')
            ->whereNotNull('watched_at')
            ->where('year_only', false)
            ->orderByDesc('watched_at')
            ->get()
            ->groupBy(fn ($w) => $w->watched_at->format('Y-m-d'))
            ->take(10)
            ->map(fn ($watches, $date) => [
                'date' => Carbon::parse($date)->format('d M Y'),
                'count' => $watches->count(),
                'titles' => $watches->pluck('movie.title')->filter()->join(', '),
            ])
            ->values()
            ->toArray();
    }

    private function genreBreakdown(): array
    {
        $movies = Movie::whereNotNull('genres')->with('watches')->get();
        $genres = [];

        foreach ($movies as $movie) {
            $count = $movie->watches->count();
            if ($count === 0) {
                continue;
            }

            foreach ($movie->genres ?? [] as $genre) {
                $genres[$genre] = ($genres[$genre] ?? 0) + $count;
            }
        }

        arsort($genres);

        return $genres;
    }
}
