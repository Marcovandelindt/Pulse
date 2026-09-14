<x-layouts.app title="Nintendo Switch — Sessions">
    <div class="page-header">
        <div class="page-header__left">
            <a href="{{ route('nintendo.index') }}" class="page-header__back">Nintendo Switch</a>
            <h1 class="page-header__title">All sessions</h1>
        </div>
    </div>

    @if($records->isEmpty())
        <x-ui.empty-state
            title="No sessions logged yet"
            description="Open a game and log your first session."
            icon="clock"
        />
    @else
        <x-ui.card>
            <div class="gaming-session-list">
                @foreach($records as $record)
                    @php
                        $h        = intdiv($record->minutes_played, 60);
                        $m        = $record->minutes_played % 60;
                        $duration = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                    @endphp
                    <div class="gaming-session-item">
                        <div class="gaming-session-item__game">
                            @if($record->game?->image_url)
                                <img
                                    src="{{ $record->game->image_url }}"
                                    alt="{{ $record->game->name }}"
                                    class="gaming-session-item__image"
                                >
                            @else
                                <div class="gaming-session-item__image" style="background:var(--color-bg-tertiary);border-radius:var(--radius-sm);"></div>
                            @endif
                            <div>
                                @if($record->game)
                                    <a href="{{ route('nintendo.show', $record->game) }}" class="gaming-session-item__title">
                                        {{ $record->game->name }}
                                    </a>
                                @else
                                    <span class="gaming-session-item__title">Unknown game</span>
                                @endif
                                <div style="font-size:.75rem;color:var(--color-text-muted);margin-top:.125rem;">
                                    {{ $record->date->format('D, M j Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="gaming-session-item__meta">
                            <span class="gaming-session-item__duration">{{ $duration }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <div class="mt-4">
            {{ $records->links() }}
        </div>
    @endif
</x-layouts.app>
