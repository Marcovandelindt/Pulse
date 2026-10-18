<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\RecurrenceType;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\ContactDate;
use App\Models\ContactRelationship;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

final class BuildCalendarGrid
{
    /**
     * @return array{
     *     dayEvents: array<string, list<mixed>>,
     *     workSchedules: SupportCollection<int, WorkSchedule>
     * }
     */
    public function handle(Carbon $month): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $events = CalendarEvent::query()
            ->where('starts_at', '<=', $gridEnd)
            ->where(function ($q) use ($gridStart) {
                $q->where('recurrence', '!=', RecurrenceType::None->value)
                    ->orWhere('starts_at', '>=', $gridStart)
                    ->orWhere(function ($q2) use ($gridStart) {
                        $q2->whereNotNull('ends_at')->where('ends_at', '>=', $gridStart);
                    });
            })
            ->where(function ($q) use ($gridStart) {
                $q->whereNull('recurrence_ends_at')
                    ->orWhere('recurrence_ends_at', '>=', $gridStart);
            })
            ->with('contact')
            ->get();

        $dayEvents = $this->buildDayEvents($events, $gridStart, $gridEnd);

        $allSchedules = WorkSchedule::orderBy('name')->get();
        $this->injectWorkShifts($allSchedules->where('active', true)->values(), $gridStart, $gridEnd, $dayEvents);

        $workSchedules = $allSchedules->filter(
            fn (WorkSchedule $s) => $s->valid_until === null || $s->valid_until->gte(today())
        )->values();

        $this->injectDeathAnniversaries($month, $dayEvents);
        $this->injectBirthdays($month, $dayEvents);
        $this->injectRelationshipDates($month, $dayEvents);
        $this->injectContactDates($month, $dayEvents);

        return compact('dayEvents', 'workSchedules');
    }

    /** @param array<string, list<mixed>> $dayEvents */
    private function injectDeathAnniversaries(Carbon $month, array &$dayEvents): void
    {
        Contact::whereNotNull('death_date')->get()->each(function (Contact $contact) use ($month, &$dayEvents) {
            if ($contact->death_date === null) {
                return;
            }
            try {
                $occurrence = $contact->death_date->copy()->setYear($month->year);
                if ((int) $occurrence->format('m') === $month->month
                    && $occurrence->gte($contact->death_date)) {
                    $dayEvents[$occurrence->format('Y-m-d')][] = ['is_death_anniversary' => true, 'contact' => $contact];
                }
            } catch (\Exception $e) {
                Log::debug('Skipped invalid death anniversary date', ['contact_id' => $contact->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /** @param array<string, list<mixed>> $dayEvents */
    private function injectBirthdays(Carbon $month, array &$dayEvents): void
    {
        Contact::whereNotNull('birthdate')->whereNull('death_date')->get()->each(function (Contact $contact) use ($month, &$dayEvents) {
            if ($contact->birthdate === null) {
                return;
            }
            try {
                $bday = $contact->birthdate->copy()->setYear($month->year);
                if ((int) $bday->format('m') === $month->month) {
                    $dayEvents[$bday->format('Y-m-d')][] = ['is_birthday' => true, 'contact' => $contact];
                }
            } catch (\Exception $e) {
                Log::debug('Skipped invalid birthday date', ['contact_id' => $contact->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /** @param array<string, list<mixed>> $dayEvents */
    private function injectRelationshipDates(Carbon $month, array &$dayEvents): void
    {
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
                } catch (\Exception $e) {
                    Log::debug('Skipped invalid relationship date', ['relationship_id' => $rel->id, 'error' => $e->getMessage()]);
                }
            });
    }

    /** @param array<string, list<mixed>> $dayEvents */
    private function injectContactDates(Carbon $month, array &$dayEvents): void
    {
        ContactDate::with('contact')->get()->each(function (ContactDate $cd) use ($month, &$dayEvents) {
            try {
                $occurrence = $cd->date->copy()->setYear($month->year);
                if ((int) $occurrence->format('m') === $month->month) {
                    $dayEvents[$occurrence->format('Y-m-d')][] = ['is_anniversary' => true, 'contact_date' => $cd];
                }
            } catch (\Exception $e) {
                Log::debug('Skipped invalid contact date', ['contact_date_id' => $cd->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /**
     * @param  SupportCollection<int, WorkSchedule>  $schedules
     * @param  array<string, list<mixed>>  $dayEvents
     */
    private function injectWorkShifts(
        SupportCollection $schedules,
        Carbon $gridStart,
        Carbon $gridEnd,
        array &$dayEvents,
    ): void {
        if ($schedules->isEmpty()) {
            return;
        }

        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $iso = $cursor->dayOfWeekIso;
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

    /**
     * @param  Collection<int, CalendarEvent>  $events
     * @return array<string, list<mixed>>
     */
    private function buildDayEvents(Collection $events, Carbon $gridStart, Carbon $gridEnd): array
    {
        $dayEvents = [];

        foreach ($events as $event) {
            foreach ($this->getOccurrencesInRange($event, $gridStart, $gridEnd) as $date) {
                $dayEvents[$date][] = $event;
            }
        }

        return $dayEvents;
    }

    /** @return list<string> */
    private function getOccurrencesInRange(CalendarEvent $event, Carbon $gridStart, Carbon $gridEnd): array
    {
        $eventStart = $event->starts_at->copy()->startOfDay();

        return match ($event->recurrence) {
            RecurrenceType::None => $this->resolveOneTime($event, $eventStart, $gridStart, $gridEnd),
            RecurrenceType::Weekly => $this->resolveWeekly($event, $eventStart, $gridStart, $gridEnd),
            RecurrenceType::Monthly => $this->resolveMonthly($event, $eventStart, $gridStart),
            RecurrenceType::Yearly => $this->resolveYearly($event, $eventStart, $gridStart),
        };
    }

    /** @return list<string> */
    private function resolveOneTime(CalendarEvent $event, Carbon $eventStart, Carbon $gridStart, Carbon $gridEnd): array
    {
        if ($eventStart->between($gridStart, $gridEnd)) {
            return [$eventStart->format('Y-m-d')];
        }

        if ($event->ends_at) {
            $eventEnd = $event->ends_at->copy()->startOfDay();
            if ($eventEnd->gte($gridStart) && $eventStart->lt($gridStart)) {
                return [$gridStart->format('Y-m-d')];
            }
        }

        return [];
    }

    /** @return list<string> */
    private function resolveWeekly(CalendarEvent $event, Carbon $eventStart, Carbon $gridStart, Carbon $gridEnd): array
    {
        $dates = [];
        $cursor = $gridStart->copy();

        while ($cursor->dayOfWeek !== $eventStart->dayOfWeek) {
            $cursor->addDay();
        }

        while ($cursor->lte($gridEnd)) {
            if ($cursor->gte($eventStart)
                && (! $event->recurrence_ends_at || $cursor->lte($event->recurrence_ends_at))) {
                $dates[] = $cursor->format('Y-m-d');
            }
            $cursor->addWeek();
        }

        return $dates;
    }

    /** @return list<string> */
    private function resolveMonthly(CalendarEvent $event, Carbon $eventStart, Carbon $gridStart): array
    {
        try {
            $occurrence = $gridStart->copy()->setDay($eventStart->day);
            if ((int) $occurrence->format('m') === $gridStart->month
                && $occurrence->gte($eventStart)
                && (! $event->recurrence_ends_at || $occurrence->lte($event->recurrence_ends_at))) {
                return [$occurrence->format('Y-m-d')];
            }
        } catch (\Exception $e) {
            Log::debug('Skipped monthly recurrence for invalid day', ['event_id' => $event->id, 'error' => $e->getMessage()]);
        }

        return [];
    }

    /** @return list<string> */
    private function resolveYearly(CalendarEvent $event, Carbon $eventStart, Carbon $gridStart): array
    {
        try {
            $occurrence = $eventStart->copy()->setYear($gridStart->year);
            if ((int) $occurrence->format('m') === $gridStart->month
                && $occurrence->gte($eventStart)
                && (! $event->recurrence_ends_at || $occurrence->lte($event->recurrence_ends_at))) {
                return [$occurrence->format('Y-m-d')];
            }
        } catch (\Exception $e) {
            Log::debug('Skipped yearly recurrence for invalid date', ['event_id' => $event->id, 'error' => $e->getMessage()]);
        }

        return [];
    }
}
