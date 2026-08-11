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
use Carbon\Carbon;
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

        $stepGoal = HealthEntry::stepGoal();
        $daysInMonth = $month->daysInMonth;
        $entryCount = $entries->count();
        $avgSteps = $entries->whereNotNull('steps')->avg('steps');
        $goalMetCount = $entries->filter(fn (HealthEntry $e) => $e->meetsStepGoal())->count();
        $streak = $this->currentStreak();

        return view('pages.health.index', compact(
            'month', 'entries', 'stepGoal', 'daysInMonth',
            'entryCount', 'avgSteps', 'goalMetCount', 'streak',
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

    private function currentStreak(): int
    {
        $streak = 0;
        $check = now()->startOfDay();
        $goal = HealthEntry::stepGoal();

        while (true) {
            $entry = HealthEntry::whereDate('date', $check)->first();

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
