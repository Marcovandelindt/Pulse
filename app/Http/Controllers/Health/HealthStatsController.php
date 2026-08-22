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

        $personalRecords = $this->personalRecords();

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
        ));
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
