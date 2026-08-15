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

    <x-ui.card title="All Sessions">
        @if($sessions->isEmpty())
            <p class="text-sm" style="color: var(--color-text-muted)">No sessions recorded yet.</p>
        @else
            <div class="gaming-session-list">
                @foreach($sessions as $session)
                    <div class="gaming-session-item">
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
                            <span class="gaming-session-item__date">{{ $session->started_at->format('d M Y, H:i') }} – {{ $session->end_time->format('H:i') }}</span>
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
