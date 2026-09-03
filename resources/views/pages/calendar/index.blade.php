<x-layouts.app title="Calendar">

    @php
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $today     = now()->startOfDay();

        $firstDay    = $month->copy()->startOfMonth();
        $lastDay     = $month->copy()->endOfMonth();
        $startOffset = ($firstDay->dayOfWeek + 6) % 7; // Monday first
        $totalCells  = (int) ceil(($startOffset + $lastDay->day) / 7) * 7;

        $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    @endphp

    <div class="calendar-page" x-data="calendarModal">

        {{-- Header --}}
        <div class="calendar-header">
            <div class="calendar-header__nav">
                <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}" class="btn btn--secondary btn--sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <span class="calendar-header__title">{{ $month->format('F Y') }}</span>
                <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="btn btn--secondary btn--sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
                @if (! $month->isSameMonth($today))
                    <a href="{{ route('calendar.index') }}" class="btn btn--secondary btn--sm">Today</a>
                @endif
            </div>
            <button @click="openCreate('{{ $today->format('Y-m-d') }}')" class="btn btn--primary btn--sm" type="button">
                + Add event
            </button>
        </div>

        {{-- Work schedule manager --}}
        <div class="schedule-manager" x-data="scheduleManager" @open-schedule-edit.window="openEdit($event.detail)">
            <div class="schedule-manager__header" @click="panelOpen = !panelOpen">
                <div class="schedule-manager__title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Work schedules
                    <span class="schedule-manager__count">{{ $workSchedules->count() }}</span>
                </div>
                <div class="schedule-manager__actions">
                    <button @click.stop="openCreate()" class="btn btn--secondary btn--sm" type="button">+ Add</button>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="flex-shrink:0;transition:transform 150ms ease;" :class="panelOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <div x-show="panelOpen" x-transition class="schedule-manager__body">
                @if ($workSchedules->isEmpty())
                    <p class="schedule-manager__empty">No work schedules yet. Add one to see your shifts on the calendar.</p>
                @else
                    <div class="schedule-manager__list">
                        @foreach ($workSchedules as $schedule)
                            @php
                                $scheduleJson = json_encode([
                                    'id'          => $schedule->id,
                                    'name'        => $schedule->name,
                                    'days'        => $schedule->days,
                                    'start_time'  => $schedule->start_time,
                                    'end_time'    => $schedule->end_time,
                                    'color'       => $schedule->color,
                                    'valid_from'  => $schedule->valid_from?->format('Y-m-d'),
                                    'valid_until' => $schedule->valid_until?->format('Y-m-d'),
                                    'active'      => $schedule->active,
                                ]);
                            @endphp
                            <div class="schedule-manager__item">
                                <span class="schedule-manager__dot" style="background: {{ $schedule->effectiveColor() }};"></span>
                                <span class="schedule-manager__name">{{ $schedule->name }}</span>
                                <span class="schedule-manager__meta">
                                    {{ $schedule->dayLabels() }} &middot; {{ $schedule->start_time }}–{{ $schedule->end_time }}
                                    @if ($schedule->valid_from || $schedule->valid_until)
                                        &middot; {{ $schedule->valid_from?->format('d M Y') ?? '…' }}–{{ $schedule->valid_until?->format('d M Y') ?? '…' }}
                                    @endif
                                </span>
                                @if (! $schedule->active)
                                    <span class="schedule-manager__badge">inactive</span>
                                @endif
                                <button
                                    @click="openEdit({{ $scheduleJson }})"
                                    class="btn btn--secondary btn--sm"
                                    type="button">Edit</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Schedule modal --}}
            <div class="modal" x-show="modalOpen" x-transition @keydown.escape.window="modalOpen = false" style="display:none;">
                <div class="modal__backdrop" @click="modalOpen = false"></div>
                <div class="modal__panel">
                    <div class="modal__header">
                        <h2 class="modal__title" x-text="mode === 'edit' ? 'Edit work schedule' : 'New work schedule'"></h2>
                        <button @click="modalOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
                    </div>

                    <form x-ref="scheduleForm" method="POST" :action="formAction">
                        @csrf
                        <input type="hidden" name="_method" :value="mode === 'edit' ? 'PATCH' : ''">
                        <input type="hidden" name="redirect_month" value="{{ $month->format('Y-m') }}">

                        <div class="modal__body">
                            {{-- Name --}}
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" x-model="name" class="form-input" required placeholder="e.g. Early shift">
                            </div>

                            {{-- Days --}}
                            <div class="form-group">
                                <label class="form-label">Days</label>
                                <div class="day-checkboxes">
                                    @foreach (['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'] as $num => $label)
                                        <label :class="{ 'day-checkbox--active': days.includes('{{ $num }}') }" class="day-checkbox">
                                            <input type="checkbox" name="days[]" value="{{ $num }}" x-model="days">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Start / end time --}}
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Start time</label>
                                    <input type="time" name="start_time" x-model="startTime" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">End time</label>
                                    <input type="time" name="end_time" x-model="endTime" class="form-input" required>
                                </div>
                            </div>

                            {{-- Valid from / until --}}
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Valid from <span class="form-label__optional">optional</span></label>
                                    <input type="date" name="valid_from" x-model="validFrom" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Valid until <span class="form-label__optional">optional</span></label>
                                    <input type="date" name="valid_until" x-model="validUntil" class="form-input">
                                </div>
                            </div>

                            {{-- Active --}}
                            <div class="form-group form-group--inline">
                                <input type="checkbox" name="active" id="schedule_active" value="1" x-model="active" class="form-checkbox">
                                <label for="schedule_active" class="form-label form-label--inline">Active (show on calendar)</label>
                            </div>
                        </div>

                        <div class="modal__footer">
                            <div class="flex items-center gap-3">
                                <button type="submit" class="btn btn--primary">
                                    <span x-text="mode === 'edit' ? 'Save changes' : 'Add schedule'"></span>
                                </button>
                                <button type="button" @click="modalOpen = false" class="btn btn--secondary">Cancel</button>
                            </div>
                            <div x-show="mode === 'edit'">
                                <button type="button" @click="confirmDelete()" class="btn btn--danger btn--sm">Remove</button>
                            </div>
                        </div>
                    </form>

                    <form x-ref="deleteScheduleForm" method="POST" :action="`/calendar/schedules/${scheduleId}`" style="display:none;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_month" value="{{ $month->format('Y-m') }}">
                    </form>
                </div>
            </div>
        </div>

        {{-- Day-of-week header --}}
        <div class="calendar-grid">
            @foreach ($dayNames as $name)
                <div class="calendar-day-header">{{ $name }}</div>
            @endforeach

            {{-- Day cells --}}
            @for ($i = 0; $i < $totalCells; $i++)
                @php
                    $cellDate    = $firstDay->copy()->subDays($startOffset - $i);
                    $isThisMonth = $cellDate->month === $month->month;
                    $isToday     = $cellDate->isSameDay($today);
                    $isWeekend   = $cellDate->isWeekend();
                    $dateKey     = $cellDate->format('Y-m-d');
                    $events      = $dayEvents[$dateKey] ?? [];
                @endphp

                <div class="calendar-cell
                    {{ ! $isThisMonth ? 'calendar-cell--other-month' : '' }}
                    {{ $isToday ? 'calendar-cell--today' : '' }}
                    {{ $isWeekend && $isThisMonth ? 'calendar-cell--weekend' : '' }}"
                    @if ($isThisMonth) @click="openCreate('{{ $dateKey }}')" @endif>

                    <span class="calendar-cell__number">{{ $cellDate->day }}</span>

                    @if (count($events) > 0)
                        <div class="calendar-cell__events">
                            @foreach (array_slice($events, 0, 3) as $event)
                                @if (is_array($event) && ($event['is_work_shift'] ?? false))
                                    @php $ws = $event['schedule']; @endphp
                                    <span class="calendar-pill calendar-pill--work-shift"
                                          style="background: {{ $ws->rgbaColor(0.13) }}; color: {{ $ws->effectiveColor() }}; cursor: pointer;"
                                          title="{{ $ws->name }}: {{ $ws->start_time }}–{{ $ws->end_time }}"
                                          @click.stop="$dispatch('open-schedule-edit', {{ json_encode(['id' => $ws->id, 'name' => $ws->name, 'days' => $ws->days, 'start_time' => $ws->start_time, 'end_time' => $ws->end_time, 'color' => $ws->color, 'valid_from' => $ws->valid_from?->format('Y-m-d'), 'valid_until' => $ws->valid_until?->format('Y-m-d'), 'active' => $ws->active]) }})">
                                        <span class="calendar-pill__dot"></span>
                                        {{ $ws->name }} · {{ $ws->start_time }}–{{ $ws->end_time }}
                                    </span>
                                @elseif (is_array($event) && ($event['is_relationship_date'] ?? false))
                                    @php $rel = $event['relationship']; @endphp
                                    <span class="calendar-pill calendar-pill--anniversary"
                                          title="{{ $rel->typeLabel() }}: {{ $rel->contact->name }} & {{ $rel->relatedContact->name }}">
                                        <span class="calendar-pill__dot"></span>
                                        {{ $rel->contact->name }} & {{ $rel->relatedContact->name }}
                                    </span>
                                @elseif (is_array($event) && ($event['is_death_anniversary'] ?? false))
                                    @php $contact = $event['contact']; @endphp
                                    <span class="calendar-pill calendar-pill--death-anniversary"
                                          title="{{ $contact->name }} — passed away {{ $contact->death_date->format('d M Y') }}">
                                        <span class="calendar-pill__dot"></span>
                                        Sterfdag: {{ $contact->name }}
                                    </span>
                                @elseif (is_array($event) && ($event['is_anniversary'] ?? false))
                                    @php $cd = $event['contact_date']; @endphp
                                    <span class="calendar-pill calendar-pill--anniversary"
                                          title="{{ $cd->label }} — {{ $cd->contact->name }}">
                                        <span class="calendar-pill__dot"></span>
                                        {{ $cd->label }} · {{ $cd->contact->name }}
                                    </span>
                                @elseif (is_array($event) && ($event['is_birthday'] ?? false))
                                    @php $contact = $event['contact']; @endphp
                                    <span class="calendar-pill calendar-pill--birthday" title="{{ $contact->name }}'s birthday">
                                        <span class="calendar-pill__dot"></span>
                                        {{ $contact->name }}
                                        @if ($contact->birthdate && ! $contact->birth_year_unknown)
                                            ({{ now()->year - $contact->birthdate->year }})
                                        @endif
                                    </span>
                                @else
                                    @php
                                        $eventData = json_encode([
                                            'id'                  => $event->id,
                                            'title'               => $event->title,
                                            'description'         => $event->description,
                                            'type'                => $event->type->value,
                                            'all_day'             => $event->all_day,
                                            'starts_at_date'      => $event->starts_at->format('Y-m-d'),
                                            'starts_at_time'      => $event->starts_at->format('H:i'),
                                            'ends_at_date'        => $event->ends_at?->format('Y-m-d'),
                                            'ends_at_time'        => $event->ends_at?->format('H:i'),
                                            'recurrence'          => $event->recurrence->value,
                                            'recurrence_ends_at'  => $event->recurrence_ends_at?->format('Y-m-d'),
                                        ]);
                                    @endphp
                                    <span class="calendar-pill {{ $event->type->bgClass() }}"
                                        data-event="{{ $eventData }}"
                                        @click.stop="openEdit(JSON.parse($el.dataset.event))"
                                        title="{{ $event->title }}">
                                        <span class="calendar-pill__dot"></span>
                                        {{ $event->title }}
                                    </span>
                                @endif
                            @endforeach

                            @if (count($events) > 3)
                                <span class="calendar-pill__more">+{{ count($events) - 3 }} more</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endfor
        </div>

        {{-- Event modal --}}
        <div class="modal" x-show="open" x-transition @keydown.escape.window="open = false" style="display:none;">
            <div class="modal__backdrop" @click="open = false"></div>
            <div class="modal__panel">
                <div class="modal__header">
                    <h2 class="modal__title" x-text="mode === 'edit' ? 'Edit event' : 'New event'"></h2>
                    <button @click="open = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
                </div>

                <form x-ref="eventForm" method="POST" action="/calendar">
                    @csrf
                    <input type="hidden" name="_method" value="">

                    <div class="modal__body">
                        {{-- Title --}}
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" x-model="title" class="form-input" required placeholder="Event title">
                        </div>

                        {{-- Type --}}
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="type" x-model="type" class="form-select">
                                @foreach ($eventTypes as $eventType)
                                    <option value="{{ $eventType->value }}">{{ $eventType->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- All day toggle --}}
                        <div class="form-group form-group--inline">
                            <input type="checkbox" name="all_day" id="all_day" value="1" x-model="allDay" class="form-checkbox">
                            <label for="all_day" class="form-label form-label--inline">All day</label>
                        </div>

                        {{-- Start date/time --}}
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Start date</label>
                                <input type="date" name="starts_at_date" x-model="startsAtDate" class="form-input" required>
                            </div>
                            <div class="form-group" x-show="!allDay">
                                <label class="form-label">Start time</label>
                                <input type="time" name="starts_at_time" x-model="startsAtTime" class="form-input">
                            </div>
                        </div>

                        {{-- End date/time --}}
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">End date <span class="form-label__optional">optional</span></label>
                                <input type="date" name="ends_at_date" x-model="endsAtDate" class="form-input">
                            </div>
                            <div class="form-group" x-show="!allDay">
                                <label class="form-label">End time</label>
                                <input type="time" name="ends_at_time" x-model="endsAtTime" class="form-input">
                            </div>
                        </div>

                        {{-- Recurrence --}}
                        <div class="form-group">
                            <label class="form-label">Repeat</label>
                            <select name="recurrence" x-model="recurrence" class="form-select">
                                @foreach ($recurrenceTypes as $recurrenceType)
                                    <option value="{{ $recurrenceType->value }}">{{ $recurrenceType->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" x-show="recurrence !== 'none'">
                            <label class="form-label">Repeat ends <span class="form-label__optional">optional</span></label>
                            <input type="date" name="recurrence_ends_at" x-model="recurrenceEndsAt" class="form-input">
                        </div>

                        {{-- Description --}}
                        <div class="form-group">
                            <label class="form-label">Notes <span class="form-label__optional">optional</span></label>
                            <textarea name="description" x-model="description" class="form-textarea" rows="3" placeholder="Add a note..."></textarea>
                        </div>
                    </div>

                    <div class="modal__footer">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="submitForm()" class="btn btn--primary">
                                <span x-text="mode === 'edit' ? 'Save changes' : 'Add event'"></span>
                            </button>
                            <button type="button" @click="open = false" class="btn btn--secondary">Cancel</button>
                        </div>
                        <div x-show="mode === 'edit'">
                            <button type="button" @click="confirmDelete()" class="btn btn--danger btn--sm">Remove</button>
                        </div>
                    </div>
                </form>

                <form x-ref="deleteForm" method="POST" :action="`/calendar/${eventId}`" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>

    </div>

</x-layouts.app>
