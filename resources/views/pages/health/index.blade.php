<x-layouts.app title="Health">

<div
    x-data="{
        addOpen:   false,
        editOpen:  false,
        goalOpen:  false,
        addDate:   '',
        editEntry: null,
        openEdit(entry) {
            this.editEntry = entry;
            this.editOpen  = true;
        }
    }"
    @keydown.escape.window="addOpen = false; editOpen = false; goalOpen = false"
>

    {{-- Header --}}
    <x-layout.page-header title="Health">
        <x-slot:actions>
            <button @click="goalOpen = true" class="btn btn--secondary btn--sm" type="button">
                Goal: {{ number_format($stepGoal) }}
            </button>
            <a href="{{ route('health.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
            <a href="{{ route('health.export') }}" class="btn btn--secondary btn--sm">Export CSV</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Stat cards --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Entries this month"
            :value="$entryCount"
        />
        <x-stats.stat-card
            label="Avg steps / day"
            :value="$avgSteps ? number_format((int) round($avgSteps)) : '—'"
            icon="heart"
        />
        <x-stats.stat-card
            label="Goal met"
            :value="$weekdayEntryCount > 0 ? $goalMetCount . ' / ' . $weekdayEntryCount . ' days' : '—'"
        />
        <x-stats.stat-card
            label="Km this month"
            :value="number_format($thisMonthKm, 1, '.', '') . ' km'"
        />
    </div>

    {{-- Month navigation + calendar --}}
    <div class="health-month-nav">
        <a href="{{ route('health.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"
           class="btn btn--secondary btn--sm">&larr;</a>

        <h2 class="health-month-nav__title">{{ $month->format('F Y') }}</h2>

        <div class="flex gap-2">
            @if (! $month->isCurrentMonth())
                <a href="{{ route('health.index') }}" class="btn btn--secondary btn--sm">Today</a>
            @endif
            @if ($month->copy()->addMonth()->lessThanOrEqualTo(now()))
                <a href="{{ route('health.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}"
                   class="btn btn--secondary btn--sm">&rarr;</a>
            @endif
        </div>
    </div>

    {{-- Calendar --}}
    <div class="health-calendar">
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
            <div class="health-calendar__day-header">{{ $day }}</div>
        @endforeach

        @php $startDow = (int) $month->copy()->startOfMonth()->isoFormat('E'); @endphp
        @for ($i = 1; $i < $startDow; $i++)
            <div class="health-calendar__cell health-calendar__cell--empty"></div>
        @endfor

        @for ($day = 1; $day <= $daysInMonth; $day++)
            @php
                $date      = $month->copy()->setDay($day);
                $key       = $date->format('Y-m-d');
                $entry     = $entries->get($key);
                $isToday   = $date->isToday();
                $isFuture  = $date->isFuture();
                $isWeekend = $date->isWeekend();
                $meetsGoal = ! $isWeekend && $entry !== null && $entry->meetsStepGoal($calendarGoals[$key]);

                $cellClass = 'health-calendar__cell';
                if ($isFuture)         $cellClass .= ' health-calendar__cell--future';
                elseif ($isWeekend)    $cellClass .= ' health-calendar__cell--weekend';
                elseif ($meetsGoal)    $cellClass .= ' health-calendar__cell--goal-met';
                elseif ($entry)        $cellClass .= ' health-calendar__cell--has-entry';
                else                   $cellClass .= ' health-calendar__cell--empty-day';
                if ($isToday)          $cellClass .= ' health-calendar__cell--today';
            @endphp

            <div
                class="{{ $cellClass }}"
                @if (! $isFuture)
                    @if ($entry)
                        @click="openEdit({{ json_encode([
                            'id'    => $entry->id,
                            'date'  => $entry->date->format('Y-m-d'),
                            'steps' => $entry->steps,
                            'notes' => $entry->notes,
                        ]) }})"
                    @else
                        @click="addDate = '{{ $key }}'; addOpen = true"
                    @endif
                @endif
            >
                <span class="health-calendar__day-number">{{ $day }}</span>
                @if ($entry?->steps !== null)
                    <span class="health-calendar__steps">{{ number_format($entry->steps) }}</span>
                @elseif ($entry)
                    <span class="health-calendar__steps health-calendar__steps--no-data">—</span>
                @endif
                @if ($meetsGoal)
                    <span class="health-calendar__goal-indicator" title="Goal met">✓</span>
                @elseif ($isWeekend && ! $isFuture)
                    <svg class="health-calendar__weekend-indicator" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" title="Weekend — exempt">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                @endif
            </div>
        @endfor
    </div>

    @if (! $month->isFuture())
        <div class="mt-4 flex justify-end">
            <button @click="addDate = '{{ now()->format('Y-m-d') }}'; addOpen = true"
                    class="btn btn--primary">
                + Add entry
            </button>
        </div>
    @endif

    {{-- Goal Modal --}}
    <div class="modal" x-show="goalOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="goalOpen = false"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title">Step goals</h2>
                <button @click="goalOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>

            <div class="flex flex-col gap-5 p-5">
                {{-- Existing goals --}}
                @if ($allGoals->isNotEmpty())
                    <div class="flex flex-col gap-1">
                        @foreach ($allGoals as $goal)
                            @php
                                $isActive = $goal->effective_from->isPast() || $goal->effective_from->isToday();
                                $nextGoal = $allGoals->filter(fn ($g) => $g->effective_from->isAfter($goal->effective_from))->last();
                                $endDate  = $nextGoal
                                    ? $nextGoal->effective_from->copy()->subDay()->format('d M Y')
                                    : null;
                            @endphp
                            <div class="step-goal-row {{ $loop->first ? 'step-goal-row--active' : '' }}">
                                <div class="step-goal-row__info">
                                    <span class="step-goal-row__steps">{{ number_format($goal->steps) }}</span>
                                    <span class="step-goal-row__period">
                                        from {{ $goal->effective_from->format('d M Y') }}
                                        @if ($endDate) – {{ $endDate }} @else <span class="step-goal-row__current">current</span> @endif
                                    </span>
                                </div>
                                <form method="POST" action="{{ route('health.goal.destroy', $goal) }}" onsubmit="return confirm('Remove this goal?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--icon btn--danger" title="Remove goal">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                    <div class="step-goal-divider"></div>
                @endif

                {{-- New goal form --}}
                <form method="POST" action="{{ route('health.goal.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <p class="text-sm" style="color: var(--color-text-muted)">Add a new goal</p>
                    <div class="form-group">
                        <label class="form-label" for="goal-steps">Daily step goal</label>
                        <input type="number" id="goal-steps" name="steps" class="form-input"
                               value="{{ $stepGoal }}" min="1" max="100000" required
                               x-effect="if (goalOpen) $nextTick(() => $el.select())">
                        <x-form.error name="steps" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="goal-from">Effective from</label>
                        <input type="date" id="goal-from" name="effective_from" class="form-input"
                               value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required>
                        <x-form.error name="effective_from" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="goalOpen = false" class="btn btn--secondary">Cancel</button>
                        <button type="submit" class="btn btn--primary">Save goal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Modal --}}
    <div class="modal" x-show="addOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="addOpen = false"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title">Add health entry</h2>
                <button @click="addOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>

            <form method="POST" action="{{ route('health.store') }}">
                @csrf
                <div class="flex flex-col gap-4">
                    <div class="form-group">
                        <label class="form-label" for="add-date">Date</label>
                        <input type="date" id="add-date" name="date" class="form-input"
                               x-bind:value="addDate" max="{{ now()->format('Y-m-d') }}" required>
                        <x-form.error name="date" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add-steps">Steps</label>
                        <input type="number" id="add-steps" name="steps" class="form-input"
                               placeholder="e.g. 8500" min="0" max="100000"
                               x-effect="if (addOpen) $nextTick(() => $el.focus())">
                        <x-form.error name="steps" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add-notes">Notes</label>
                        <textarea id="add-notes" name="notes" class="form-textarea" rows="3"
                                  placeholder="Optional note..."></textarea>
                        <x-form.error name="notes" />
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" @click="addOpen = false" class="btn btn--secondary">Cancel</button>
                    <button type="submit" class="btn btn--primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div class="modal" x-show="editOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="editOpen = false"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title" x-text="editEntry ? 'Edit — ' + editEntry.date : 'Edit entry'"></h2>
                <button @click="editOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>

            <template x-if="editEntry">
                <form method="POST" x-bind:action="`/health/${editEntry.id}`">
                    @csrf
                    @method('PATCH')
                    <div class="flex flex-col gap-4">
                        <div class="form-group">
                            <label class="form-label" for="edit-steps">Steps</label>
                            <input type="number" id="edit-steps" name="steps" class="form-input"
                                   x-bind:value="editEntry.steps ?? ''"
                                   placeholder="e.g. 8500" min="0" max="100000">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="edit-notes">Notes</label>
                            <textarea id="edit-notes" name="notes" class="form-textarea" rows="3"
                                      x-text="editEntry.notes ?? ''"></textarea>
                        </div>
                    </div>
                    <div class="modal__footer">
                        <form method="POST" x-bind:action="`/health/${editEntry.id}`" class="mr-auto">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm"
                                    onclick="return confirm('Delete this entry?')">Delete</button>
                        </form>
                        <button type="button" @click="editOpen = false" class="btn btn--secondary">Cancel</button>
                        <button type="submit" class="btn btn--primary">Update</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <x-layout.notification />

</div>
</x-layouts.app>
