<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Actions\Health\CreateHealthEntry;
use App\Actions\Health\DeleteHealthEntry;
use App\Actions\Health\HealthEntryData;
use App\Actions\Health\UpdateHealthEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\StoreHealthEntryRequest;
use App\Http\Requests\Health\UpdateHealthEntryRequest;
use App\Models\HealthEntry;
use App\Models\StepGoal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HealthEntryController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $entries = HealthEntry::query()
            ->between($month, $month->copy()->endOfMonth())
            ->orderBy('date')
            ->get()
            ->keyBy(fn (HealthEntry $e) => $e->date->format('Y-m-d'));

        /** @var Collection<int, StepGoal> $allGoals */
        $allGoals = StepGoal::orderByDesc('effective_from')->get();
        $stepGoal = StepGoal::current();
        $monthGoal = $month->isCurrentMonth()
            ? $stepGoal
            : StepGoal::forDate($month->copy()->endOfMonth());
        $daysInMonth = $month->daysInMonth;

        // Applicable goal per calendar day (goals are sorted desc by effective_from)
        $calendarGoals = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $month->copy()->setDay($d);
            $key = $date->format('Y-m-d');
            $calendarGoals[$key] = $allGoals->first(fn (StepGoal $g) => ! $g->effective_from->isAfter($date))?->steps ?? 10000;
        }

        $entryCount = $entries->count();
        $avgSteps = $entries->whereNotNull('steps')->avg('steps');
        $weekdayEntries = $entries->filter(fn (HealthEntry $e) => ! $e->date->isWeekend());
        $goalMetCount = $weekdayEntries->filter(
            fn (HealthEntry $e) => $e->meetsStepGoal($calendarGoals[$e->date->format('Y-m-d')] ?? $stepGoal)
        )->count();
        $weekdayEntryCount = $weekdayEntries->count();
        $thisMonthKm = round((int) $entries->whereNotNull('steps')->sum('steps') * 0.00066, 1);

        return view('pages.health.index', compact(
            'month', 'entries', 'stepGoal', 'monthGoal', 'allGoals', 'calendarGoals', 'daysInMonth',
            'entryCount', 'avgSteps', 'goalMetCount', 'weekdayEntryCount', 'thisMonthKm',
        ));
    }

    public function store(StoreHealthEntryRequest $request, CreateHealthEntry $action): RedirectResponse
    {
        $action->handle(HealthEntryData::fromRequest($request));

        return redirect()->route('health.index', ['month' => $request->validated('date') ? Carbon::parse($request->validated('date'))->format('Y-m') : null])
            ->with('success', 'Entry saved.');
    }

    public function update(UpdateHealthEntryRequest $request, HealthEntry $entry, UpdateHealthEntry $action): RedirectResponse
    {
        $action->handle($entry, HealthEntryData::fromRequest($request));

        return redirect()->route('health.index', ['month' => $entry->date->format('Y-m')])
            ->with('success', 'Entry updated.');
    }

    public function destroy(HealthEntry $entry, DeleteHealthEntry $action): RedirectResponse
    {
        $month = $entry->date->format('Y-m');
        $action->handle($entry);

        return redirect()->route('health.index', ['month' => $month])
            ->with('success', 'Entry deleted.');
    }

    /** @param Collection<int, StepGoal> $allGoals */
    private function currentStreak(Collection $allGoals): int
    {
        $streak = 0;
        $check = now()->startOfDay();

        while (true) {
            $entry = HealthEntry::whereDate('date', $check)->first();
            $goal = $allGoals->first(fn (StepGoal $g) => ! $g->effective_from->isAfter($check))?->steps ?? 10000;

            if ($entry === null || $entry->steps === null || $entry->steps < $goal) {
                if ($streak === 0 && $check->isToday()) {
                    $check = $check->subDay();

                    continue;
                }
                break;
            }

            $streak++;
            $check = $check->subDay();
        }

        return $streak;
    }
}
