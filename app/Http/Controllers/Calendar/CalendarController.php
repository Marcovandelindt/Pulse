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
            'month', 'dayEvents', 'eventTypes', 'recurrenceTypes'
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
