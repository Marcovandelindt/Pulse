<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Enums\EventType;
use App\Enums\RecurrenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\ContactDate;
use App\Models\ContactRelationship;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', (string) $request->get('month'))->startOfMonth()
            : now()->startOfMonth();

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $events = CalendarEvent::query()
            ->where('starts_at', '<=', $monthEnd)
            ->where(function ($q) use ($monthStart) {
                $q->where('recurrence', '!=', RecurrenceType::None->value)
                    ->orWhere('starts_at', '>=', $monthStart)
                    ->orWhere(function ($q2) use ($monthStart) {
                        $q2->whereNotNull('ends_at')->where('ends_at', '>=', $monthStart);
                    });
            })
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('recurrence_ends_at')
                    ->orWhere('recurrence_ends_at', '>=', $monthStart);
            })
            ->with('contact')
            ->get();

        $dayEvents = $this->buildDayEvents($events, $monthStart, $monthEnd);

        $allSchedules = WorkSchedule::orderBy('name')->get();
        $this->injectWorkShifts($allSchedules->where('active', true)->values(), $monthStart, $monthEnd, $dayEvents);

        $workSchedules = $allSchedules->filter(
            fn (WorkSchedule $s) => $s->valid_until === null || $s->valid_until->gte(today())
        )->values();

        Contact::whereNotNull('death_date')->get()->each(function (Contact $contact) use ($month, &$dayEvents) {
            try {
                $occurrence = $contact->death_date->copy()->setYear($month->year);
                if ((int) $occurrence->format('m') === $month->month
                    && $occurrence->gte($contact->death_date)) {
                    $dayEvents[$occurrence->format('Y-m-d')][] = ['is_death_anniversary' => true, 'contact' => $contact];
                }
            } catch (\Exception) {
                // skip invalid dates
            }
        });

        Contact::whereNotNull('birthdate')->whereNull('death_date')->get()->each(function (Contact $contact) use ($month, &$dayEvents) {
            try {
                $bday = $contact->birthdate->copy()->setYear($month->year);
                if ((int) $bday->format('m') === $month->month) {
                    $key = $bday->format('Y-m-d');
                    $dayEvents[$key][] = ['is_birthday' => true, 'contact' => $contact];
                }
            } catch (\Exception) {
                // skip invalid dates (e.g. Feb 29 in non-leap year)
            }
        });

        ContactRelationship::with(['contact', 'relatedContact'])
            ->whereNotNull('date')
            ->get()
            ->each(function (ContactRelationship $rel) use ($month, &$dayEvents) {
                try {
                    $occurrence = $rel->date->copy()->setYear($month->year);
                    if ((int) $occurrence->format('m') === $month->month
                        && $occurrence->gte($rel->date)) {
                        $dayEvents[$occurrence->format('Y-m-d')][] = ['is_relationship_date' => true, 'relationship' => $rel];
                    }
                } catch (\Exception) {
                    // skip invalid dates
                }
            });

        ContactDate::with('contact')->get()->each(function (ContactDate $cd) use ($month, &$dayEvents) {
            try {
                $occurrence = $cd->date->copy()->setYear($month->year);
                if ((int) $occurrence->format('m') === $month->month) {
                    $key = $occurrence->format('Y-m-d');
                    $dayEvents[$key][] = ['is_anniversary' => true, 'contact_date' => $cd];
                }
            } catch (\Exception) {
                // skip invalid dates
            }
        });

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

    /**
     * @param  \Illuminate\Support\Collection<int, WorkSchedule>  $schedules
     * @param  array<string, list<mixed>>  $dayEvents
     */
    private function injectWorkShifts(
        \Illuminate\Support\Collection $schedules,
        Carbon $monthStart,
        Carbon $monthEnd,
        array &$dayEvents,
    ): void {
        if ($schedules->isEmpty()) {
            return;
        }

        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $iso     = $cursor->dayOfWeekIso; // 1=Mon, 7=Sun
            $dateKey = $cursor->format('Y-m-d');

            foreach ($schedules as $schedule) {
                if (! in_array($iso, $schedule->days, true)) {
                    continue;
                }
                if ($schedule->valid_from && $cursor->lt($schedule->valid_from)) {
                    continue;
                }
                if ($schedule->valid_until && $cursor->gt($schedule->valid_until)) {
                    continue;
                }

                // Prepend so work shifts appear before other events in the cell
                $dayEvents[$dateKey] = array_merge(
                    [['is_work_shift' => true, 'schedule' => $schedule]],
                    $dayEvents[$dateKey] ?? [],
                );
            }

            $cursor->addDay();
        }
    }

    /** @return array<string, list<mixed>> */
    private function buildDayEvents(Collection $events, Carbon $monthStart, Carbon $monthEnd): array
    {
        $dayEvents = [];

        foreach ($events as $event) {
            foreach ($this->getOccurrencesInMonth($event, $monthStart, $monthEnd) as $date) {
                $dayEvents[$date][] = $event;
            }
        }

        return $dayEvents;
    }

    /** @return list<string> */
    private function getOccurrencesInMonth(CalendarEvent $event, Carbon $monthStart, Carbon $monthEnd): array
    {
        $dates = [];
        $eventStart = $event->starts_at->copy()->startOfDay();

        switch ($event->recurrence) {
            case RecurrenceType::None:
                if ($eventStart->between($monthStart, $monthEnd)) {
                    $dates[] = $eventStart->format('Y-m-d');
                } elseif ($event->ends_at) {
                    $eventEnd = $event->ends_at->copy()->startOfDay();
                    if ($eventEnd->gte($monthStart) && $eventStart->lt($monthStart)) {
                        $dates[] = $monthStart->format('Y-m-d');
                    }
                }
                break;

            case RecurrenceType::Weekly:
                $cursor = $monthStart->copy();
                while ($cursor->dayOfWeek !== $eventStart->dayOfWeek) {
                    $cursor->addDay();
                }
                while ($cursor->lte($monthEnd)) {
                    if ($cursor->gte($eventStart)
                        && (! $event->recurrence_ends_at || $cursor->lte($event->recurrence_ends_at))) {
                        $dates[] = $cursor->format('Y-m-d');
                    }
                    $cursor->addWeek();
                }
                break;

            case RecurrenceType::Monthly:
                try {
                    $occurrence = $monthStart->copy()->setDay($eventStart->day);
                    if ((int) $occurrence->format('m') === $monthStart->month
                        && $occurrence->gte($eventStart)
                        && (! $event->recurrence_ends_at || $occurrence->lte($event->recurrence_ends_at))) {
                        $dates[] = $occurrence->format('Y-m-d');
                    }
                } catch (\Exception) {
                    // day doesn't exist in this month (e.g. 31st in a 30-day month)
                }
                break;

            case RecurrenceType::Yearly:
                try {
                    $occurrence = $eventStart->copy()->setYear($monthStart->year);
                    if ((int) $occurrence->format('m') === $monthStart->month
                        && $occurrence->gte($eventStart)
                        && (! $event->recurrence_ends_at || $occurrence->lte($event->recurrence_ends_at))) {
                        $dates[] = $occurrence->format('Y-m-d');
                    }
                } catch (\Exception) {
                    // invalid date
                }
                break;
        }

        return $dates;
    }
}
