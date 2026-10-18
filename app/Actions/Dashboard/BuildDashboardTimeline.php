<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Data\ActivityItem;
use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class BuildDashboardTimeline
{
    private const int DAYS = 7;

    /** @return Collection<int, ActivityItem> */
    public function handle(): Collection
    {
        $watches = EpisodeWatch::with(['episode.season.series'])
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays(self::DAYS)->startOfDay())
            ->orderByDesc('watched_at')
            ->get();

        // Group by series + calendar date so binge sessions collapse into one item
        $watchActivities = $watches
            ->groupBy(fn (EpisodeWatch $w) => $w->episode->season->series->id.'_'.((string) $w->watched_at?->toDateString()))
            ->map(function (Collection $group): ActivityItem {
                $first = $group->first() ?? throw new \RuntimeException('Empty group from groupBy()');
                $series = $first->episode->season->series;
                $count = $group->count();

                $episodeLabel = fn (EpisodeWatch $w): string => 'S'.$w->episode->season->season_number.'E'.$w->episode->episode_number.' — '.$w->episode->name;

                $subtitle = $count === 1
                    ? $episodeLabel($first)
                    : $count.' episodes';

                $episodes = $count > 1
                    ? array_values($group->sortBy('watched_at')->map($episodeLabel)->all())
                    : [];

                return new ActivityItem(
                    type: 'episode_watch',
                    title: $series->name,
                    subtitle: $subtitle,
                    imageUrl: $series->poster_url,
                    occurredAt: $first->watched_at ?? throw new \RuntimeException('watched_at cannot be null after whereNotNull filter'),
                    isPinned: false,
                    episodes: $episodes,
                    url: route('tv.show', $series),
                );
            })
            ->values();

        $stepActivities = HealthEntry::withSteps()
            ->where('date', '>=', now()->subDays(self::DAYS)->startOfDay())
            ->where('date', '<', today())
            ->orderByDesc('date')
            ->get()
            ->map(fn (HealthEntry $entry): ActivityItem => new ActivityItem(
                type: 'steps',
                title: number_format((int) $entry->steps).' steps',
                subtitle: 'Daily steps',
                imageUrl: null,
                occurredAt: $entry->date->startOfDay(),
                isPinned: true,
            ));

        $movieActivities = MovieWatch::with('movie')
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays(self::DAYS)->startOfDay())
            ->orderByDesc('watched_at')
            ->get()
            ->map(fn (MovieWatch $w): ActivityItem => new ActivityItem(
                type: 'movie_watch',
                title: $w->movie->title,
                subtitle: $w->movie->release_date?->year
                    ? 'Movie · '.$w->movie->release_date->year
                    : 'Movie',
                imageUrl: $w->movie->poster_url,
                occurredAt: $w->watched_at ?? throw new \RuntimeException('watched_at cannot be null after whereNotNull filter'),
                isPinned: false,
                url: route('movies.show', $w->movie),
            ));

        $musicActivities = Play::with(['track.artists'])
            ->where('played_at', '>=', now()->subDays(self::DAYS)->startOfDay())
            ->orderBy('played_at')
            ->get()
            ->groupBy(fn (Play $p) => $p->played_at->toDateString())
            ->map(function (Collection $group, string $date): ActivityItem {
                $count = $group->count();
                $tracks = array_values($group->map(fn (Play $p) => $p->track->title.' — '.$p->track->artists_string)->all());

                return new ActivityItem(
                    type: 'music',
                    title: 'Spotify',
                    subtitle: 'Listened to '.($count === 1 ? '1 track' : $count.' tracks'),
                    imageUrl: null,
                    occurredAt: Carbon::parse($date)->startOfDay(),
                    isPinned: false,
                    episodes: $tracks,
                );
            })
            ->values();

        $playstationActivities = PlayStationSession::with('game')
            ->where('started_at', '>=', now()->subDays(self::DAYS)->startOfDay())
            ->orderBy('started_at')
            ->get()
            ->groupBy(fn (PlayStationSession $s) => $s->started_at->toDateString())
            ->map(function (Collection $group, string $date): ActivityItem {
                $count = $group->count();
                $totalMinutes = (int) $group->sum('duration_minutes');

                $sessionLabel = fn (PlayStationSession $s): string => $s->game->label.' · '.$s->started_at->format('H:i').' – '.$s->end_time->format('H:i');

                if ($count === 1) {
                    $session = $group->first() ?? throw new \RuntimeException('Empty group from groupBy()');

                    return new ActivityItem(
                        type: 'playstation',
                        title: $session->game->label,
                        subtitle: $session->started_at->format('H:i').' – '.$session->end_time->format('H:i').' · '.($this->formatMinutes($session->duration_minutes) ?? ''),
                        imageUrl: $session->game->image_url,
                        occurredAt: $session->started_at,
                        isPinned: false,
                    );
                }

                return new ActivityItem(
                    type: 'playstation',
                    title: 'PlayStation',
                    subtitle: $count.' sessions · '.($this->formatMinutes($totalMinutes) ?? ''),
                    imageUrl: null,
                    occurredAt: Carbon::parse($date)->startOfDay(),
                    isPinned: false,
                    episodes: array_values($group->sortBy('started_at')->map($sessionLabel)->all()),
                );
            })
            ->values();

        return $watchActivities
            ->concat($movieActivities)
            ->concat($stepActivities)
            ->concat($musicActivities)
            ->concat($playstationActivities)
            ->sort(fn (ActivityItem $a, ActivityItem $b): int => $this->compareActivities($a, $b))
            ->values();
    }

    private function compareActivities(ActivityItem $a, ActivityItem $b): int
    {
        $dateCompare = $b->occurredAt->toDateString() <=> $a->occurredAt->toDateString();
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        $pinnedCompare = $a->isPinned <=> $b->isPinned;
        if ($pinnedCompare !== 0) {
            return $pinnedCompare;
        }

        return $b->occurredAt->timestamp <=> $a->occurredAt->timestamp;
    }

    private function formatMinutes(int $minutes): ?string
    {
        if ($minutes === 0) {
            return null;
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}m";
        }

        return $h > 0 ? "{$h}h" : "{$m}m";
    }
}
