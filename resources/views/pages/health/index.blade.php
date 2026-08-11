<x-layouts.app title="Health">

    {{-- Header --}}
    <x-layout.page-header title="Health">
        <x-slot:actions>
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
            :value="$entryCount > 0 ? $goalMetCount . ' / ' . $entryCount . ' days' : '—'"
        />
        <x-stats.stat-card
            label="Current streak"
            :value="$streak . ($streak === 1 ? ' day' : ' days')"
        />
    </div>

    {{-- Month navigation + calendar --}}
    <div
        x-data="{
            addOpen: false,
            editOpen: false,
            addDate: '',
            editEntry: null,
            openEdit(entry) {
                this.editEntry = entry;
                this.editOpen = true;
            }
        }"
        @keydown.escape.window="addOpen = false; editOpen = false"
    >
        {{-- Month nav --}}
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
            {{-- Day headers --}}
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                <div class="health-calendar__day-header">{{ $day }}</div>
            @endforeach

            {{-- Empty cells before month start (Monday = 1) --}}
            @php $startDow = (int) $month->copy()->startOfMonth()->isoFormat('E'); @endphp
            @for ($i = 1; $i < $startDow; $i++)
                <div class="health-calendar__cell health-calendar__cell--empty"></div>
            @endfor

            {{-- Day cells --}}
            @for ($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $date      = $month->copy()->setDay($day);
                    $key       = $date->format('Y-m-d');
                    $entry     = $entries->get($key);
                    $isToday   = $date->isToday();
                    $isFuture  = $date->isFuture();
                    $meetsGoal = $entry?->meetsStepGoal();

                    $cellClass = 'health-calendar__cell';
                    if ($isFuture)     $cellClass .= ' health-calendar__cell--future';
                    elseif ($meetsGoal) $cellClass .= ' health-calendar__cell--goal-met';
                    elseif ($entry)     $cellClass .= ' health-calendar__cell--has-entry';
                    else                $cellClass .= ' health-calendar__cell--empty-day';
                    if ($isToday)      $cellClass .= ' health-calendar__cell--today';
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
                    @endif
                </div>
            @endfor
        </div>

        {{-- Add entry button --}}
        @if (! $month->isFuture())
            <div class="mt-4 flex justify-end">
                <button @click="addDate = '{{ now()->format('Y-m-d') }}'; addOpen = true"
                        class="btn btn--primary">
                    + Add entry
                </button>
            </div>
        @endif

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
                                   placeholder="e.g. 8500" min="0" max="100000">
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

    </div>

    <x-layout.notification />

</x-layouts.app>
