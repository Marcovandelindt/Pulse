<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use App\Models\StepGoal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;

final class HealthStatsController extends Controller
{
    public function index(): View
    {
        /** @var Collection<int, StepGoal> $allGoals */
        $allGoals = StepGoal::orderByDesc('effective_from')->get();
        $stepGoal = StepGoal::current();

        $thisWeekAvg = HealthEntry::thisWeek()->withSteps()->avg('steps') ?? 0;
        $lastWeekAvg = HealthEntry::lastWeek()->withSteps()->avg('steps') ?? 0;
        $weekChange = $lastWeekAvg > 0
            ? round((((float) $thisWeekAvg - (float) $lastWeekAvg) / (float) $lastWeekAvg) * 100, 1)
            : 0;

        $thisMonthAvg = HealthEntry::thisMonth()->withSteps()->avg('steps') ?? 0;
        $lastMonthAvg = HealthEntry::lastMonth()->withSteps()->avg('steps') ?? 0;
        $monthChange = $lastMonthAvg > 0
            ? round((((float) $thisMonthAvg - (float) $lastMonthAvg) / (float) $lastMonthAvg) * 100, 1)
            : 0;

        $totalEntries = HealthEntry::withSteps()->count();
        $goalMetEntries = HealthEntry::withSteps()->where('steps', '>=', $stepGoal)->count();
        $goalRate = $totalEntries > 0 ? round(($goalMetEntries / $totalEntries) * 100) : 0;

        [$currentStreak, $longestStreak] = $this->calculateStreaks($allGoals);

        $weekdayPatterns = $this->weekdayPatterns();
        $monthlyHistory = $this->monthlyHistory();
        $goalHistory = StepGoal::orderByDesc('effective_from')->get();

        $allTimeSteps = (int) HealthEntry::withSteps()->sum('steps');
        $allTimeKm    = round($allTimeSteps * 0.00075, 1);
        $thisYearKm   = round((int) HealthEntry::withSteps()->whereYear('date', now()->year)->sum('steps') * 0.00075, 1);

        $personalRecords    = $this->personalRecords();
        $goalPerformance    = $this->goalPerformanceExtended((int) $stepGoal, $allGoals);
        $stepsDistribution  = $this->stepsDistribution((int) $stepGoal);
        $consistency        = $this->consistencyStats();

        $distanceComparisons = collect([
            ['label' => 'Amsterdam → Paris',          'km' => 500],
            ['label' => 'Around the Netherlands',     'km' => 1075],
            ['label' => 'Amsterdam → Rome',           'km' => 1750],
            ['label' => 'Amsterdam → Moscow',         'km' => 2500],
            ['label' => 'Around the Earth',           'km' => 40075],
        ])->map(fn (array $ref) => [
            'label'   => $ref['label'],
            'km'      => $ref['km'],
            'times'   => $allTimeKm > 0 ? round($allTimeKm / $ref['km'], 1) : 0,
            'percent' => $allTimeKm > 0 ? min(100, round(($allTimeKm / $ref['km']) * 100)) : 0,
        ]);

        return view('pages.health.stats', compact(
            'stepGoal',
            'thisWeekAvg', 'lastWeekAvg', 'weekChange',
            'thisMonthAvg', 'lastMonthAvg', 'monthChange',
            'totalEntries', 'goalMetEntries', 'goalRate',
            'currentStreak', 'longestStreak',
            'weekdayPatterns',
            'monthlyHistory',
            'goalHistory',
            'allTimeSteps', 'allTimeKm', 'thisYearKm',
            'distanceComparisons',
            'personalRecords',
            'goalPerformance',
            'stepsDistribution',
            'consistency',
        ));
    }

    /** @return array<string, mixed> */
    private function consistencyStats(): array
    {
        $dates = HealthEntry::withSteps()
            ->orderBy('date')
            ->pluck('date');

        if ($dates->isEmpty()) {
            return ['logRate' => 0, 'loggingStreak' => 0, 'avgGap' => null, 'missedDays' => 0];
        }

        $first       = $dates->first();
        $last        = $dates->last();
        $totalSpan   = $first->diffInDays($last) + 1;
        $logRate     = $totalSpan > 0 ? round(($dates->count() / $totalSpan) * 100) : 100;
        $missedDays  = $totalSpan - $dates->count();

        // Longest logging streak (consecutive days with any entry)
        $longestLogging = 0;
        $runningLogging = 1;
        $gaps = [];

        for ($i = 1; $i < $dates->count(); $i++) {
            $diff = $dates[$i - 1]->diffInDays($dates[$i]);
            $gaps[] = $diff;

            if ($diff === 1) {
                $runningLogging++;
                $longestLogging = max($longestLogging, $runningLogging);
            } else {
                $runningLogging = 1;
            }
        }

        $longestLogging = max($longestLogging, $runningLogging);
        $avgGap = count($gaps) > 0 ? round(array_sum($gaps) / count($gaps), 1) : null;

        return [
            'logRate'        => $logRate,
            'loggingStreak'  => $longestLogging,
            'avgGap'         => $avgGap,
            'missedDays'     => $missedDays,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function stepsDistribution(int $goal): array
    {
        $buckets = [
            ['label' => '0–2.5k',   'min' => 0,     'max' => 2500],
            ['label' => '2.5–5k',   'min' => 2500,  'max' => 5000],
            ['label' => '5–7.5k',   'min' => 5000,  'max' => 7500],
            ['label' => '7.5–10k',  'min' => 7500,  'max' => 10000],
            ['label' => '10–12.5k', 'min' => 10000, 'max' => 12500],
            ['label' => '12.5k+',   'min' => 12500, 'max' => PHP_INT_MAX],
        ];

        $entries = HealthEntry::withSteps()->pluck('steps');
        $total   = $entries->count();

        return collect($buckets)->map(function (array $bucket) use ($entries, $total, $goal) {
            $count = $entries->filter(fn (int $s) => $s >= $bucket['min'] && $s < $bucket['max'])->count();

            return [
                'label'       => $bucket['label'],
                'count'       => $count,
                'percent'     => $total > 0 ? round(($count / $total) * 100) : 0,
                'isGoalZone'  => $goal >= $bucket['min'] && $goal < $bucket['max'],
            ];
        })->all();
    }

    /**
     * @param Collection<int, StepGoal> $allGoals
     * @return array<string, mixed>
     */
    private function goalPerformanceExtended(int $currentGoal, Collection $allGoals): array
    {
        $entries = HealthEntry::withSteps()->get(['date', 'steps']);

        $above150 = 0;
        $above200 = 0;
        $below50  = 0;
        $sumMet   = 0;
        $countMet = 0;
        $sumMissed   = 0;
        $countMissed = 0;

        foreach ($entries as $entry) {
            $goal = $allGoals->first(fn (StepGoal $g) => ! $g->effective_from->isAfter($entry->date))?->steps ?? 10000;
            $steps = $entry->steps ?? 0;
            $pct = $goal > 0 ? ($steps / $goal) : 0;

            if ($pct >= 2.0) $above200++;
            if ($pct >= 1.5) $above150++;
            if ($pct < 0.5)  $below50++;

            if ($steps >= $goal) {
                $sumMet += $steps;
                $countMet++;
            } else {
                $sumMissed += $steps;
                $countMissed++;
            }
        }

        $goalByMonth = HealthEntry::withSteps()
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, COUNT(*) as total, SUM(CASE WHEN steps >= ? THEN 1 ELSE 0 END) as met', [$currentGoal])
            ->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
            ->orderByDesc('month')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'month'   => Carbon::createFromFormat('Y-m', $row->month)->format('M Y'),
                'rate'    => $row->total > 0 ? round(($row->met / $row->total) * 100) : 0,
                'met'     => (int) $row->met,
                'total'   => (int) $row->total,
            ])
            ->sortBy('month')
            ->values();

        return [
            'above150'       => $above150,
            'above200'       => $above200,
            'below50'        => $below50,
            'avgStepsMet'    => $countMet > 0 ? (int) round($sumMet / $countMet) : null,
            'avgStepsMissed' => $countMissed > 0 ? (int) round($sumMissed / $countMissed) : null,
            'goalByMonth'    => $goalByMonth,
        ];
    }

    /** @return array<string, mixed> */
    private function personalRecords(): array
    {
        $bestDay = HealthEntry::withSteps()->orderByDesc('steps')->first(['date', 'steps']);

        $bestWeekRow = HealthEntry::withSteps()
            ->selectRaw('YEARWEEK(date, 1) as yw, SUM(steps) as total, MIN(date) as week_start')
            ->groupByRaw('YEARWEEK(date, 1)')
            ->orderByDesc('total')
            ->first();

        $bestMonthRow = HealthEntry::withSteps()
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, SUM(steps) as total')
            ->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
            ->orderByDesc('total')
            ->first();

        return [
            'bestDaySteps'   => $bestDay?->steps,
            'bestDayDate'    => $bestDay?->date->format('d M Y'),
            'bestWeekSteps'  => $bestWeekRow ? (int) $bestWeekRow->total : null,
            'bestWeekStart'  => $bestWeekRow ? Carbon::parse($bestWeekRow->week_start)->format('d M Y') : null,
            'bestMonthSteps' => $bestMonthRow ? (int) $bestMonthRow->total : null,
            'bestMonthLabel' => $bestMonthRow ? Carbon::createFromFormat('Y-m', $bestMonthRow->month)->format('F Y') : null,
        ];
    }

    /** @param Collection<int, StepGoal> $allGoals */
    private function calculateStreaks(Collection $allGoals): array
    {
        $entries = HealthEntry::withSteps()
            ->orderByDesc('date')
            ->get(['date', 'steps']);

        $current = 0;
        $longest = 0;
        $running = 0;
        $inCurrent = true;

        $firstEntryDate = $entries->first()?->date->startOfDay();
        $todayLogged = $firstEntryDate && $firstEntryDate->equalTo(now()->startOfDay());
        $check = $todayLogged ? now()->startOfDay() : now()->subDay()->startOfDay();

        foreach ($entries as $entry) {
            $entryDate = $entry->date->startOfDay();
            $goalForDay = $allGoals->first(fn (StepGoal $g) => ! $g->effective_from->isAfter($entry->date))?->steps ?? 10000;
            $meetsGoal = ($entry->steps ?? 0) >= $goalForDay;

            if ($inCurrent) {
                if ($entryDate->equalTo($check) && $meetsGoal) {
                    $current++;
                    $check = $check->subDay();
                } elseif ($entryDate->equalTo(now()->startOfDay()) && ! $meetsGoal) {
                    $inCurrent = false;
                    $check = now()->subDay()->startOfDay();
                } else {
                    $inCurrent = false;
                }
            }

            $running = $meetsGoal ? $running + 1 : 0;
            $longest = max($longest, $running);
        }

        $longest = max($longest, $current);

        return [$current, $longest];
    }

    private function weekdayPatterns(): SupportCollection
    {
        $days = collect([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun']);

        $raw = HealthEntry::withSteps()
            ->selectRaw('DAYOFWEEK(date) as dow, AVG(steps) as avg_steps, COUNT(*) as count')
            ->groupByRaw('DAYOFWEEK(date)')
            ->get()
            ->keyBy('dow');

        // MySQL DAYOFWEEK: 1=Sunday … 7=Saturday — remap to 1=Monday … 7=Sunday
        return $days->map(function (string $label, int $iso) use ($raw) {
            $mysqlDow = $iso === 7 ? 1 : $iso + 1;
            $row = $raw->get($mysqlDow);

            return [
                'label' => $label,
                'avg_steps' => $row ? (int) round((float) $row->avg_steps) : 0,
                'count' => $row ? $row->count : 0,
            ];
        })->values();
    }

    private function monthlyHistory(): SupportCollection
    {
        return HealthEntry::withSteps()
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, COUNT(*) as entries, SUM(steps) as total_steps, AVG(steps) as avg_steps')
            ->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
            ->orderByDesc('month')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return [
                    'month' => Carbon::createFromFormat('Y-m', $row->month)->format('F Y'),
                    'entries' => $row->entries,
                    'total_steps' => number_format((int) $row->total_steps),
                    'avg_steps' => number_format((int) round((float) $row->avg_steps)),
                ];
            });
    }
}
