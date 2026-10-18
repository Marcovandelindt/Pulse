<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Actions\Calendar\BuildCalendarGrid;
use App\Enums\EventType;
use App\Enums\RecurrenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CalendarController extends Controller
{
    public function __construct(private readonly BuildCalendarGrid $buildGrid) {}

    public function index(Request $request): View
    {
        $month = $request->filled('month')
            ? (Carbon::createFromFormat('Y-m', (string) $request->get('month')) ?: now())->startOfMonth()
            : now()->startOfMonth();

        ['dayEvents' => $dayEvents, 'workSchedules' => $workSchedules] = $this->buildGrid->handle($month);

        $eventTypes = EventType::cases();
        $recurrenceTypes = RecurrenceType::cases();

        return view('pages.calendar.index', compact(
            'month', 'dayEvents', 'eventTypes', 'recurrenceTypes', 'workSchedules'
        ));
    }

    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        CalendarEvent::create($request->validated());

        $month = Carbon::parse($request->validated('starts_at'))->format('Y-m');

        return redirect()->route('calendar.index', ['month' => $month])
            ->with('success', 'Event added.');
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event): RedirectResponse
    {
        $event->update($request->validated());

        $month = Carbon::parse($request->validated('starts_at'))->format('Y-m');

        return redirect()->route('calendar.index', ['month' => $month])
            ->with('success', 'Event updated.');
    }

    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $month = $event->starts_at->format('Y-m');
        $event->delete();

        return redirect()->route('calendar.index', ['month' => $month])
            ->with('success', 'Event removed.');
    }
}
