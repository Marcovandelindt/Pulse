<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Tv;

use App\Http\Controllers\Controller;
use App\Models\EpisodeWatch;
use App\Models\TvSeries;
use Carbon\Carbon;
use Illuminate\View\View;

final class TvStatsController extends Controller
{
    public function index(): View
    {
        $totalSeries = TvSeries::count();
        $episodesWatched = EpisodeWatch::count();
        $totalMinutes = EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->whereNotNull('tv_episodes.runtime')
            ->sum('tv_episodes.runtime');
        $totalHours = round($totalMinutes / 60, 1);

        $completed = TvSeries::completed()->count();
        $inProgress = TvSeries::inProgress()->count();
        $notStarted = TvSeries::where('episodes_watched', 0)->count();

        $firstWatch = EpisodeWatch::with('episode.season.series')
            ->whereNotNull('watched_at')
            ->oldest('watched_at')
            ->first();

        $lastWatch = EpisodeWatch::with('episode.season.series')
            ->whereNotNull('watched_at')
            ->latest('watched_at')
            ->first();

        $topSeries = TvSeries::where('episodes_watched', '>', 0)
            ->orderByDesc('episodes_watched')
            ->limit(10)
            ->get();

        $inProgressSeries = TvSeries::inProgress()
            ->orderByDesc('completion_percentage')
            ->limit(10)
            ->get();

        $genreStats = $this->genreBreakdown();
        $monthlyHistory = $this->monthlyHistory();

        return view('pages.tv.stats', compact(
            'totalSeries', 'episodesWatched', 'totalHours',
            'completed', 'inProgress', 'notStarted',
            'firstWatch', 'lastWatch',
            'topSeries', 'inProgressSeries', 'genreStats', 'monthlyHistory',
        ));
    }

    private function genreBreakdown(): array
    {
        $series = TvSeries::whereNotNull('genres')->where('episodes_watched', '>', 0)->get();
        $genres = [];

        foreach ($series as $show) {
            foreach ($show->genres ?? [] as $genre) {
                $genres[$genre] = ($genres[$genre] ?? 0) + 1;
            }
        }

        arsort($genres);

        return $genres;
    }

    private function monthlyHistory(): array
    {
        return EpisodeWatch::whereNotNull('watched_at')
            ->orderByDesc('watched_at')
            ->get(['watched_at'])
            ->groupBy(fn ($row) => $row->watched_at->format('Y-m'))
            ->take(12)
            ->map(fn ($items, $month) => [
                'month' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                'episodes' => $items->count(),
            ])
            ->values()
            ->toArray();
    }
}
