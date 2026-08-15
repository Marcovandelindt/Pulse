<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Data\ActivityItem;
use App\Http\Controllers\Controller;
use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    private const TIMELINE_DAYS = 14;
    private const TIMELINE_LIMIT = 15;

    public function index(): View
    {
        return view('pages.dashboard.index', [
            'timeline' => $this->buildTimeline(),
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

                $subtitle = $count === 1
                    ? 'S'.$first->episode->season->season_number.'E'.$first->episode->episode_number.' — '.$first->episode->name
                    : $count.' episodes';

                return new ActivityItem(
                    type: 'episode_watch',
                    title: $series->name,
                    subtitle: $subtitle,
                    imageUrl: $series->poster_url,
                    occurredAt: $first->watched_at,
                    isPinned: false,
                );
            })
            ->values();

        // Only fetch steps for days already covered by the episode watches,
        // so steps never crowd out episodes entirely.
        $since = min(
            $watches->last()?->watched_at?->toDateString() ?? today()->subDays(14)->toDateString(),
            today()->subDay()->toDateString(),
        );

        $stepActivities = HealthEntry::withSteps()
            ->where('date', '>=', $since)
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

        $sorted = $watchActivities
            ->concat($stepActivities)
            ->sort(fn (ActivityItem $a, ActivityItem $b): int => $this->compareActivities($a, $b))
            ->values();

        // If over the limit, drop the oldest episode watches first (steps stay).
        if ($sorted->count() > self::TIMELINE_LIMIT) {
            $steps    = $sorted->filter(fn (ActivityItem $i) => $i->isPinned)->values();
            $episodes = $sorted->filter(fn (ActivityItem $i) => !$i->isPinned)
                ->take(self::TIMELINE_LIMIT - $steps->count())
                ->values();

            $sorted = $steps->concat($episodes)
                ->sort(fn (ActivityItem $a, ActivityItem $b): int => $this->compareActivities($a, $b))
                ->values();
        }

        return $sorted;
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
