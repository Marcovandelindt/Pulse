<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Facades\DB;

final class BuildCrossStatsAction
{
    public function handle(): array
    {
        return [
            'day_of_week'      => $this->dayOfWeekProfile(),
            'gaming_vs_health' => $this->gamingVsHealth(),
            'music_vs_health'  => $this->musicVsHealth(),
            'media_vs_gaming'  => $this->mediaVsGaming(),
        ];
    }

    /** Average steps / plays / gaming-minutes per weekday (Mon–Sun). */
    private function dayOfWeekProfile(): array
    {
        // Steps: average per weekday across all logged days
        $stepsByDow = HealthEntry::query()
            ->whereNotNull('steps')
            ->select(DB::raw('DAYOFWEEK(date) as dow'), DB::raw('AVG(steps) as avg_steps'))
            ->groupBy('dow')
            ->pluck('avg_steps', 'dow')
            ->map(fn ($v) => (int) round((float) $v));

        // Music: first sum plays per calendar day, then average by weekday
        $musicByDow = Play::query()
            ->whereNotNull('played_at')
            ->select(
                DB::raw('DATE(played_at) as day'),
                DB::raw('DAYOFWEEK(DATE(played_at)) as dow'),
                DB::raw('COUNT(*) as daily_plays'),
            )
            ->groupBy('day', 'dow')
            ->get()
            ->groupBy('dow')
            ->map(fn ($g) => (int) round((float) $g->avg('daily_plays')));

        // Gaming: first sum minutes per calendar day, then average by weekday
        $gamingByDow = PlayStationSession::query()
            ->select(
                DB::raw('DATE(started_at) as day'),
                DB::raw('DAYOFWEEK(DATE(started_at)) as dow'),
                DB::raw('SUM(duration_minutes) as daily_minutes'),
            )
            ->groupBy('day', 'dow')
            ->get()
            ->groupBy('dow')
            ->map(fn ($g) => (int) round((float) $g->avg('daily_minutes')));

        // MySQL DAYOFWEEK: 1=Sun, 2=Mon … 7=Sat → reorder Mon–Sun
        $order = [
            ['key' => 2, 'label' => 'Mon'],
            ['key' => 3, 'label' => 'Tue'],
            ['key' => 4, 'label' => 'Wed'],
            ['key' => 5, 'label' => 'Thu'],
            ['key' => 6, 'label' => 'Fri'],
            ['key' => 7, 'label' => 'Sat'],
            ['key' => 1, 'label' => 'Sun'],
        ];

        return collect($order)
            ->map(fn ($d) => [
                'label'          => $d['label'],
                'avg_steps'      => $stepsByDow[$d['key']] ?? 0,
                'avg_plays'      => $musicByDow[$d['key']] ?? 0,
                'avg_gaming_min' => $gamingByDow[$d['key']] ?? 0,
            ])
            ->values()
            ->all();
    }

    /** Compare average steps on gaming days vs. days without gaming. */
    private function gamingVsHealth(): ?array
    {
        $gamingDates = PlayStationSession::query()
            ->select(DB::raw('DATE(started_at) as day'))
            ->distinct()
            ->pluck('day');

        if ($gamingDates->isEmpty()) {
            return null;
        }

        $avgOnGamingDays = (int) round((float) HealthEntry::query()
            ->whereNotNull('steps')
            ->whereIn('date', $gamingDates)
            ->avg('steps'));

        $avgOnRestDays = (int) round((float) HealthEntry::query()
            ->whereNotNull('steps')
            ->whereNotIn('date', $gamingDates)
            ->avg('steps'));

        if ($avgOnGamingDays === 0 && $avgOnRestDays === 0) {
            return null;
        }

        return [
            'gaming_days_count' => $gamingDates->count(),
            'avg_steps_gaming'  => $avgOnGamingDays,
            'avg_steps_rest'    => $avgOnRestDays,
            'diff_pct'          => $avgOnRestDays > 0
                ? round(($avgOnGamingDays - $avgOnRestDays) / $avgOnRestDays * 100, 1)
                : null,
        ];
    }

    /** Compare average daily music plays on high-step vs. low-step days. */
    private function musicVsHealth(): ?array
    {
        $entries = HealthEntry::query()
            ->whereNotNull('steps')
            ->get(['date', 'steps']);

        if ($entries->isEmpty()) {
            return null;
        }

        $overallAvg = (int) round((float) $entries->avg('steps'));

        $highDates = $entries->filter(fn ($e) => $e->steps >= $overallAvg)->pluck('date')->map(fn ($d) => (string) $d);
        $lowDates  = $entries->filter(fn ($e) => $e->steps < $overallAvg)->pluck('date')->map(fn ($d) => (string) $d);

        $playsByDate = Play::query()
            ->whereNotNull('played_at')
            ->select(DB::raw('DATE(played_at) as day'), DB::raw('COUNT(*) as plays'))
            ->groupBy('day')
            ->pluck('plays', 'day')
            ->map(fn ($v) => (int) $v);

        $avgHighDays = $highDates->isNotEmpty()
            ? (int) round($highDates->map(fn ($d) => $playsByDate[$d] ?? 0)->average())
            : 0;

        $avgLowDays = $lowDates->isNotEmpty()
            ? (int) round($lowDates->map(fn ($d) => $playsByDate[$d] ?? 0)->average())
            : 0;

        if ($avgHighDays === 0 && $avgLowDays === 0) {
            return null;
        }

        return [
            'overall_avg_steps' => $overallAvg,
            'avg_plays_active'  => $avgHighDays,
            'avg_plays_rest'    => $avgLowDays,
            'diff_pct'          => $avgLowDays > 0
                ? round(($avgHighDays - $avgLowDays) / $avgLowDays * 100, 1)
                : null,
        ];
    }

    /** Compare average episodes/films watched on gaming days vs. non-gaming days. */
    private function mediaVsGaming(): ?array
    {
        $gamingDates = PlayStationSession::query()
            ->select(DB::raw('DATE(started_at) as day'))
            ->distinct()
            ->pluck('day');

        if ($gamingDates->isEmpty()) {
            return null;
        }

        $episodesByDate = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (int) $v);

        $moviesByDate = MovieWatch::query()
            ->whereNotNull('watched_at')
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (int) $v);

        $mediaByDate = $episodesByDate->mergeRecursive($moviesByDate)
            ->map(fn ($v) => is_array($v) ? array_sum($v) : $v);

        $avgMediaGamingDays = $gamingDates->isNotEmpty()
            ? round($gamingDates->map(fn ($d) => $mediaByDate[(string) $d] ?? 0)->average(), 1)
            : 0;

        // Non-gaming days that had any media
        $allMediaDates = $mediaByDate->keys();
        $nonGamingMediaDates = $allMediaDates->diff($gamingDates->map(fn ($d) => (string) $d));

        $avgMediaRestDays = $nonGamingMediaDates->isNotEmpty()
            ? round($nonGamingMediaDates->map(fn ($d) => $mediaByDate[$d] ?? 0)->average(), 1)
            : 0;

        if ($avgMediaGamingDays === 0.0 && $avgMediaRestDays === 0.0) {
            return null;
        }

        return [
            'avg_media_gaming' => $avgMediaGamingDays,
            'avg_media_rest'   => $avgMediaRestDays,
        ];
    }
}
