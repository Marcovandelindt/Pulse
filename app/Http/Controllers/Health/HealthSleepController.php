<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use App\Models\HealthSleep;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;

final class HealthSleepController extends Controller
{
    private const SLEEP_GOAL_MINUTES = 480; // 8 hours

    public function index(): View
    {
        $records = HealthSleep::orderByDesc('date')->get();

        $lastSleep = $records->first();
        $avgTotal  = $records->avg('total_sleep_minutes');
        $avgDeep   = $records->avg('deep_minutes');
        $avgRem    = $records->avg('rem_minutes');
        $avgCore   = $records->avg('core_minutes');
        $avgScore    = $records->isEmpty() ? null : (int) round($records->avg(fn ($r) => $r->sleepScore()));
        $consistency = $this->sleepConsistency($records);
        $debtData    = $this->sleepDebt($records);
        $trendData   = $this->trendChartData();
        [$correlationPoints, $correlationCoefficient] = $this->correlationData();

        return view('pages.health.sleep', compact(
            'records', 'lastSleep', 'avgTotal', 'avgDeep', 'avgRem', 'avgCore', 'avgScore',
            'consistency', 'debtData', 'trendData', 'correlationPoints', 'correlationCoefficient',
        ));
    }

    /**
     * @return array{0: SupportCollection, 1: float|null}
     */
    private function correlationData(): array
    {
        $sleepRecords = HealthSleep::orderBy('date')->get(['date', 'total_sleep_minutes']);

        $nextDates = $sleepRecords->map(fn ($r) => $r->date->copy()->addDay()->format('Y-m-d'))->all();

        $stepsByDate = HealthEntry::withSteps()
            ->whereIn('date', $nextDates)
            ->get(['date', 'steps'])
            ->keyBy(fn ($e) => $e->date->format('Y-m-d'));

        $points = $sleepRecords
            ->map(function (HealthSleep $sleep) use ($stepsByDate): ?array {
                $entry = $stepsByDate->get($sleep->date->copy()->addDay()->format('Y-m-d'));
                if (! $entry) {
                    return null;
                }

                $sleepHours = round($sleep->total_sleep_minutes / 60, 1);

                return [
                    'x'     => $sleepHours,
                    'y'     => $entry->steps,
                    'label' => $sleep->date->format('d M Y') . ': ' . $sleepHours . 'h sleep → ' . number_format($entry->steps) . ' steps',
                ];
            })
            ->filter()
            ->values();

        return [$points, $this->pearsonCorrelation($points)];
    }

    private function pearsonCorrelation(SupportCollection $points): ?float
    {
        $n = $points->count();
        if ($n < 5) {
            return null;
        }

        $sumX  = $points->sum('x');
        $sumY  = $points->sum('y');
        $sumXY = $points->sum(fn ($p) => $p['x'] * $p['y']);
        $sumX2 = $points->sum(fn ($p) => $p['x'] ** 2);
        $sumY2 = $points->sum(fn ($p) => $p['y'] ** 2);

        $num = $n * $sumXY - $sumX * $sumY;
        $den = sqrt(($n * $sumX2 - $sumX ** 2) * ($n * $sumY2 - $sumY ** 2));

        return $den > 0 ? round($num / $den, 2) : null;
    }

    /** @return array<string, mixed> */
    private function trendChartData(): array
    {
        $records = HealthSleep::where('date', '>=', now()->subDays(89)->toDateString())
            ->orderBy('date')
            ->get(['date', 'total_sleep_minutes']);

        return [
            'labels' => $records->map(fn ($r) => $r->date->format('d M'))->values()->all(),
            'values' => $records->map(fn ($r) => round($r->total_sleep_minutes / 60, 2))->values()->all(),
        ];
    }

    /**
     * @param Collection<int, HealthSleep> $records
     * @return array<string, mixed>
     */
    private function sleepDebt(Collection $records): array
    {
        $last7 = HealthSleep::where('date', '>=', now()->subDays(6)->toDateString())
            ->orderByDesc('date')
            ->get(['date', 'total_sleep_minutes']);

        $weekNights  = $last7->count();
        $weekBalance = $weekNights > 0
            ? (int) $last7->sum('total_sleep_minutes') - ($weekNights * self::SLEEP_GOAL_MINUTES)
            : 0;

        $allTimeDeficit = (int) $records->sum(fn ($r) => max(0, self::SLEEP_GOAL_MINUTES - $r->total_sleep_minutes));
        $allTimeSurplus = (int) $records->sum(fn ($r) => max(0, $r->total_sleep_minutes - self::SLEEP_GOAL_MINUTES));

        return [
            'weekNights'     => $weekNights,
            'weekBalance'    => $weekBalance,
            'allTimeDeficit' => $allTimeDeficit,
            'allTimeSurplus' => $allTimeSurplus,
            'goalMinutes'    => self::SLEEP_GOAL_MINUTES,
        ];
    }

    /**
     * @param Collection<int, HealthSleep> $records
     * @return array<string, mixed>
     */
    private function sleepConsistency(Collection $records): array
    {
        if ($records->count() < 3) {
            return ['avgBedtime' => null, 'avgWakeTime' => null, 'bedVariability' => null, 'wakeVariability' => null];
        }

        // Minutes since noon — handles midnight crossing for typical bedtimes (22:00–02:00)
        $bedMins = $records->map(function (HealthSleep $r): int {
            $h = (int) $r->sleep_start->format('H');
            $m = (int) $r->sleep_start->format('i');
            return ($h * 60 + $m + 24 * 60 - 12 * 60) % (24 * 60);
        });

        $wakeMins = $records->map(function (HealthSleep $r): int {
            $h = (int) $r->sleep_end->format('H');
            $m = (int) $r->sleep_end->format('i');
            return $h * 60 + $m;
        });

        $avgBed      = (int) round($bedMins->avg());
        $avgWake     = (int) round($wakeMins->avg());
        $avgBedClock = ($avgBed + 12 * 60) % (24 * 60);

        $bedStd  = (int) round(sqrt($bedMins->map(fn ($v) => ($v - $avgBed) ** 2)->avg()));
        $wakeStd = (int) round(sqrt($wakeMins->map(fn ($v) => ($v - $avgWake) ** 2)->avg()));

        return [
            'avgBedtime'      => sprintf('%02d:%02d', intdiv($avgBedClock, 60), $avgBedClock % 60),
            'avgWakeTime'     => sprintf('%02d:%02d', intdiv($avgWake, 60), $avgWake % 60),
            'bedVariability'  => $bedStd,
            'wakeVariability' => $wakeStd,
        ];
    }
}
