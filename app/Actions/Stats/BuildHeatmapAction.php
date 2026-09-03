<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class BuildHeatmapAction
{
    /** @return array<string, int> keyed by 'Y-m-d' */
    public function steps(Carbon $start, Carbon $end): array
    {
        return HealthEntry::query()
            ->whereNotNull('steps')
            ->whereBetween('date', [$start, $end])
            ->pluck('steps', 'date')
            ->mapWithKeys(fn ($value, $key) => [
                Carbon::parse($key)->format('Y-m-d') => (int) $value,
            ])
            ->all();
    }

    /** @return array<string, int> keyed by 'Y-m-d', value = total minutes */
    public function gaming(Carbon $start, Carbon $end): array
    {
        return PlayStationSession::query()
            ->whereBetween('started_at', [$start, $end])
            ->select(DB::raw('DATE(started_at) as day'), DB::raw('SUM(duration_minutes) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value])
            ->all();
    }

    /** @return array<string, int> keyed by 'Y-m-d', value = track count */
    public function music(Carbon $start, Carbon $end): array
    {
        return Play::query()
            ->whereNotNull('played_at')
            ->whereBetween('played_at', [$start, $end])
            ->select(DB::raw('DATE(played_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value])
            ->all();
    }

    /** @return array<string, int> keyed by 'Y-m-d', value = episode + movie count */
    public function media(Carbon $start, Carbon $end): array
    {
        $episodes = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$start, $end])
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value]);

        $movies = MovieWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$start, $end])
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value]);

        return $episodes->mergeRecursive($movies)
            ->map(fn ($value) => is_array($value) ? array_sum($value) : $value)
            ->all();
    }

    /** @return list<int> years that have any data, descending */
    public function availableYears(): array
    {
        $years = collect([
            HealthEntry::query()->whereNotNull('steps')->min('date'),
            PlayStationSession::query()->min('started_at'),
            Play::query()->whereNotNull('played_at')->min('played_at'),
            EpisodeWatch::query()->whereNotNull('watched_at')->min('watched_at'),
            MovieWatch::query()->whereNotNull('watched_at')->min('watched_at'),
        ])
            ->filter()
            ->map(fn ($date) => (int) Carbon::parse($date)->format('Y'))
            ->push(now()->year)
            ->unique()
            ->sort()
            ->values();

        $earliest = $years->first() ?? now()->year;

        return collect(range(now()->year, $earliest))
            ->values()
            ->all();
    }
}
