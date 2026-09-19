<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthSleep;
use Illuminate\Database\Eloquent\Collection;
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

        return view('pages.health.sleep', compact(
            'records', 'lastSleep', 'avgTotal', 'avgDeep', 'avgRem', 'avgCore', 'avgScore',
            'consistency', 'debtData', 'trendData',
        ));
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
