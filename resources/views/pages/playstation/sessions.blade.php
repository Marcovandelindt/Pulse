<x-layouts.app title="Sessions — PlayStation">

    <x-layout.page-header title="PlayStation Sessions">
        <x-slot:actions>
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
        <x-stats.stat-card
            label="Total Sessions"
            :value="number_format($totalSessions)"
            icon="play"
        />
        <x-stats.stat-card
            label="Avg Duration"
            :value="$avgDuration >= 60
                ? intdiv($avgDuration, 60) . 'h ' . ($avgDuration % 60) . 'm'
                : $avgDuration . 'm'"
            icon="clock"
        />
        <x-stats.stat-card
            label="Longest Session"
            :value="$longestSession >= 60
                ? intdiv($longestSession, 60) . 'h ' . ($longestSession % 60) . 'm'
                : $longestSession . 'm'"
            icon="clock"
        />
        <x-stats.stat-card
            label="Total Hours"
            :value="$totalHours . 'h'"
            icon="clock"
        />
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('playstation.sessions') }}"
          class="mb-4 rounded-xl border p-5"
          style="background: var(--color-bg-secondary); border-color: var(--color-border);">
        <div class="flex flex-wrap items-end gap-x-5 gap-y-4">

            <div class="flex flex-col gap-1.5 flex-1" style="min-width: 180px;">
                <label class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">Game</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search game…" class="form-input">
            </div>

            <div class="flex flex-col gap-1.5" style="min-width: 140px; flex: 0 1 160px;">
                <label class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">Min. duration (min)</label>
                <input type="number" name="min_duration" value="{{ $minDuration }}" min="1" placeholder="e.g. 30" class="form-input">
            </div>

            <div class="flex flex-col gap-1.5 flex-1" style="min-width: 180px;">
                <label class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">Category</label>
                <select name="category_id" class="form-input">
                    <option value="">All categories</option>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}" @selected($categoryId == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1.5" style="min-width: 140px; flex: 0 1 160px;">
                <label class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input">
            </div>

            <div class="flex flex-col gap-1.5" style="min-width: 140px; flex: 0 1 160px;">
                <label class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input">
            </div>

            <div class="flex gap-2 items-end pb-px">
                <button type="submit" class="btn btn--primary btn--sm">Filter</button>
                @if($search || $minDuration || $categoryId || $dateFrom || $dateTo)
                    <a href="{{ route('playstation.sessions') }}" class="btn btn--secondary btn--sm">Clear</a>
                @endif
            </div>

        </div>
    </form>

    <x-ui.card title="All Sessions">
        @if($sessions->isEmpty())
            <p class="text-sm" style="color: var(--color-text-muted)">No sessions found for the selected filters.</p>
        @else
            <div class="gaming-session-list">
                @foreach($sessions as $session)
                    <div class="gaming-session-item">
                        @if($session->game->image_url)
                            <img src="{{ $session->game->image_url }}"
                                 alt="{{ $session->game->name }}"
                                 class="gaming-session-item__image">
                        @else
                            <div class="gaming-session-item__image"></div>
                        @endif

                        <div class="gaming-session-item__game">
                            <a href="{{ route('playstation.show', $session->game) }}" class="gaming-session-item__title">
                                {{ $session->game->name }}
                            </a>
                            <span class="gaming-platform-badge" style="background: {{ $session->game->platformColor() }}">
                                {{ $session->game->platform }}
                            </span>
                        </div>

                        <div class="gaming-session-item__meta">
                            <span class="gaming-session-item__duration">{{ $session->formatted_duration }}</span>
                            <span class="gaming-session-item__date">{{ $session->started_at->format('l, d M Y, H:i') }} – {{ $session->end_time->format('H:i') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $sessions->links() }}
            </div>
        @endif
    </x-ui.card>

</x-layouts.app>
