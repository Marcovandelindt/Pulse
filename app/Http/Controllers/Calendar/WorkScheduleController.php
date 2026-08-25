<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreWorkScheduleRequest;
use App\Http\Requests\Calendar\UpdateWorkScheduleRequest;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WorkScheduleController extends Controller
{
    public function store(StoreWorkScheduleRequest $request): RedirectResponse
    {
        WorkSchedule::create($request->validated());

        $month = $request->input('redirect_month', now()->format('Y-m'));

        return redirect()->route('calendar.index', ['month' => $month])
            ->with('success', 'Work schedule added.');
    }

    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $schedule): RedirectResponse
    {
        $schedule->update($request->validated());

        $month = $request->input('redirect_month', now()->format('Y-m'));

        return redirect()->route('calendar.index', ['month' => $month])
            ->with('success', 'Work schedule updated.');
    }

    public function destroy(Request $request, WorkSchedule $schedule): RedirectResponse
    {
        $month = $request->input('redirect_month', now()->format('Y-m'));
        $schedule->delete();

        return redirect()->route('calendar.index', ['month' => $month])
            ->with('success', 'Work schedule removed.');
    }
}
