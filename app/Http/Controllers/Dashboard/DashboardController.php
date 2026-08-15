<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Data\ActivityItem;
use App\Http\Controllers\Controller;
use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\Play;
use App\Services\Spotify\SpotifyTrackService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    private const TIMELINE_DAYS = 7;

    public function __construct(
        private readonly SpotifyTrackService $trackService,
    ) {}

    public function index(): View
    {
        $stepsThisWeek = HealthEntry::withSteps()->thisWeek()->sum('steps');

        try {
            $currentlyPlaying = $this->trackService->getCurrentlyPlaying();
        } catch (\Throwable) {
            $currentlyPlaying = null;
        }

        $recentPlay = $currentlyPlaying === null
            ? Play::with(['track.album', 'track.artists'])->orderByDesc('played_at')->first()
            : null;

        return view('pages.dashboard.index', [
            'timeline'         => $this->buildTimeline(),
            'stepsThisWeek'    => $stepsThisWeek > 0 ? number_format((int) $stepsThisWeek) : null,
            'currentlyPlaying' => $currentlyPlaying,
            'recentPlay'       => $recentPlay,
        ]);
    }

    /** @return Collection<int, ActivityItem> */
    private function buildTimeline(): Collection
    {
        $watches = EpisodeWatch::with(['episode.season.series'])
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays(self::TIMELINE_DAYS)->startOfDay())
            ->orderByDesc('watched_at')
            ->get();

        // Group by series + calendar date so binge sessions collapse into one item
        $watchActivities = $watches
            ->groupBy(fn (EpisodeWatch $w) => $w->episode->season->series->id.'_'.$w->watched_at->toDateString())
            ->map(function (Collection $group): ActivityItem {
                $first = $group->first();
                $series = $first->episode->season->series;
                $count = $group->count();

                $episodeLabel = fn (EpisodeWatch $w): string =>
                    'S'.$w->episode->season->season_number.'E'.$w->episode->episode_number.' — '.$w->episode->name;

                $subtitle = $count === 1
                    ? $episodeLabel($first)
                    : $count.' episodes';

                $episodes = $count > 1
                    ? $group->sortBy('watched_at')->map($episodeLabel)->values()->all()
                    : [];

                return new ActivityItem(
                    type: 'episode_watch',
                    title: $series->name,
                    subtitle: $subtitle,
                    imageUrl: $series->poster_url,
                    occurredAt: $first->watched_at,
                    isPinned: false,
                    episodes: $episodes,
                );
            })
            ->values();

        $stepActivities = HealthEntry::withSteps()
            ->where('date', '>=', now()->subDays(self::TIMELINE_DAYS)->startOfDay())
            ->where('date', '<', today())
            ->orderByDesc('date')
            ->get()
            ->map(fn (HealthEntry $entry): ActivityItem => new ActivityItem(
                type: 'steps',
                title: number_format($entry->steps).' steps',
                subtitle: 'Daily steps',
                imageUrl: null,
                occurredAt: $entry->date->startOfDay(),
                isPinned: true,
            ));

        return $watchActivities
            ->concat($stepActivities)
            ->sort(fn (ActivityItem $a, ActivityItem $b): int => $this->compareActivities($a, $b))
            ->values();
    }

    private function compareActivities(ActivityItem $a, ActivityItem $b): int
    {
        $dateCompare = $b->occurredAt->toDateString() <=> $a->occurredAt->toDateString();
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        // Within the same date: episode watches before steps
        $pinnedCompare = $a->isPinned <=> $b->isPinned;
        if ($pinnedCompare !== 0) {
            return $pinnedCompare;
        }

        return $b->occurredAt->timestamp <=> $a->occurredAt->timestamp;
    }
}
