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
    private const DAYS = 365;

    /** @return array<string, int> keyed by 'Y-m-d' */
    public function steps(): array
    {
        return HealthEntry::query()
            ->whereNotNull('steps')
            ->where('date', '>=', $this->since())
            ->pluck('steps', 'date')
            ->mapWithKeys(fn ($value, $key) => [
                Carbon::parse($key)->format('Y-m-d') => (int) $value,
            ])
            ->all();
    }

    /** @return array<string, int> keyed by 'Y-m-d', value = total minutes */
    public function gaming(): array
    {
        return PlayStationSession::query()
            ->where('started_at', '>=', $this->since())
            ->select(DB::raw('DATE(started_at) as day'), DB::raw('SUM(duration_minutes) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value])
            ->all();
    }

    /** @return array<string, int> keyed by 'Y-m-d', value = track count */
    public function music(): array
    {
        return Play::query()
            ->whereNotNull('played_at')
            ->where('played_at', '>=', $this->since())
            ->select(DB::raw('DATE(played_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value])
            ->all();
    }

    /** @return array<string, int> keyed by 'Y-m-d', value = episode + movie count */
    public function media(): array
    {
        $episodes = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', $this->since())
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value]);

        $movies = MovieWatch::query()
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', $this->since())
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($value, $key) => [$key => (int) $value]);

        return $episodes->mergeRecursive($movies)
            ->map(fn ($value) => is_array($value) ? array_sum($value) : $value)
            ->all();
    }

    private function since(): Carbon
    {
        return now()->subDays(self::DAYS - 1)->startOfDay();
    }
}
