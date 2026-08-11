<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class HealthStatsController extends Controller
{
    public function index(): View
    {
        $stepGoal = HealthEntry::stepGoal();

        $thisWeekAvg = HealthEntry::thisWeek()->withSteps()->avg('steps') ?? 0;
        $lastWeekAvg = HealthEntry::lastWeek()->withSteps()->avg('steps') ?? 0;
        $weekChange = $lastWeekAvg > 0
            ? round((($thisWeekAvg - $lastWeekAvg) / $lastWeekAvg) * 100, 1)
            : 0;

        $thisMonthAvg = HealthEntry::thisMonth()->withSteps()->avg('steps') ?? 0;
        $lastMonthAvg = HealthEntry::lastMonth()->withSteps()->avg('steps') ?? 0;
        $monthChange = $lastMonthAvg > 0
            ? round((($thisMonthAvg - $lastMonthAvg) / $lastMonthAvg) * 100, 1)
            : 0;

        $totalEntries = HealthEntry::withSteps()->count();
        $goalMetEntries = HealthEntry::withSteps()->where('steps', '>=', $stepGoal)->count();
        $goalRate = $totalEntries > 0 ? round(($goalMetEntries / $totalEntries) * 100) : 0;

        [$currentStreak, $longestStreak] = $this->calculateStreaks($stepGoal);

        $weekdayPatterns = $this->weekdayPatterns();

        $monthlyHistory = $this->monthlyHistory();

        return view('pages.health.stats', compact(
            'stepGoal',
            'thisWeekAvg', 'lastWeekAvg', 'weekChange',
            'thisMonthAvg', 'lastMonthAvg', 'monthChange',
            'totalEntries', 'goalMetEntries', 'goalRate',
            'currentStreak', 'longestStreak',
            'weekdayPatterns',
            'monthlyHistory',
        ));
    }

    private function calculateStreaks(int $goal): array
    {
        $entries = HealthEntry::withSteps()
            ->orderByDesc('date')
            ->get(['date', 'steps']);

        $current = 0;
        $longest = 0;
        $running = 0;
        $check = now()->startOfDay();
        $inCurrent = true;

        foreach ($entries as $entry) {
            $entryDate = $entry->date->startOfDay();
            $meetsGoal = $entry->steps >= $goal;

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

    private function weekdayPatterns(): Collection
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
                'avg_steps' => $row ? (int) round($row->avg_steps) : 0,
                'count' => $row ? $row->count : 0,
            ];
        })->values();
    }

    private function monthlyHistory(): Collection
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
                    'avg_steps' => number_format((int) round($row->avg_steps)),
                ];
            });
    }
}
