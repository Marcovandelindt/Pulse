<x-layouts.app :title="$game->name">

    <x-layout.page-header :title="$game->name">
        <x-slot:actions>
            <a href="{{ route('playstation.edit', $game) }}" class="btn btn--secondary btn--sm">Edit</a>
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
             style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
            {{ session('success') }}
        </div>
    @endif

    <div class="gaming-hero mb-6">
        @if($game->image_url)
            <div class="gaming-hero__cover">
                <img src="{{ $game->image_url }}" alt="{{ $game->name }}" class="gaming-hero__img">
            </div>
        @endif
        <div class="gaming-hero__info">
            <div class="flex items-center gap-2 mb-2">
                <span class="gaming-platform-badge" style="background: {{ $game->platformColor() }}">
                    {{ $game->platform }}
                </span>
                @if($game->backlog_status)
                    <x-ui.badge :color="$game->backlog_status->color()">{{ $game->backlog_status->label() }}</x-ui.badge>
                @endif
                @if($game->main_story_completed)
                    <x-ui.badge color="green">Story Complete</x-ui.badge>
                @endif
            </div>
            <h2 class="text-2xl font-bold mb-1" style="color: var(--color-text-primary)">{{ $game->name }}</h2>
            @if($game->play_mode)
                <p class="text-sm mb-3" style="color: var(--color-text-muted)">{{ $game->play_mode->label() }}</p>
            @endif
            <div class="flex flex-wrap gap-4">
                @if($game->user_rating)
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">Your Rating</div>
                        <div class="text-lg font-bold" style="color: var(--color-text-primary)">{{ $game->user_rating }}/10</div>
                    </div>
                @endif
                @if($game->critic_rating)
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">Critic Rating</div>
                        <div class="text-lg font-bold" style="color: var(--color-text-primary)">{{ $game->critic_rating }}/10</div>
                    </div>
                @endif
                @if($game->price)
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide" style="color: var(--color-text-muted)">Price</div>
                        <div class="text-lg font-bold" style="color: var(--color-text-primary)">€{{ number_format($game->price, 2) }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
        <x-stats.stat-card
            label="Hours Played"
            :value="$game->formatted_hours"
            icon="clock"
        />
        <x-stats.stat-card
            label="Sessions"
            :value="$game->calculated_sessions"
            icon="play"
        />
        <x-stats.stat-card
            label="Avg Session"
            :value="$game->formatted_avg_session"
            icon="clock"
        />
        <x-stats.stat-card
            label="Completion"
            :value="number_format($game->completion_percentage, 1) . '%'"
            icon="heart"
        />
    </div>

    @if($monthlyStats->isNotEmpty())
        <x-ui.card title="Monthly Playtime" class="mb-6">
            <canvas
                data-chart="bar"
                data-chart-data="{{ json_encode([
                    'labels' => $monthlyStats->pluck('month'),
                    'values' => $monthlyStats->pluck('hours'),
                ]) }}"
                style="max-height: 200px;"
            ></canvas>
        </x-ui.card>
    @endif

    <x-ui.card title="Sessions">
        @if($recentSessions->isEmpty())
            <p class="text-sm" style="color: var(--color-text-muted)">No sessions recorded yet.</p>
        @else
            <div class="gaming-session-list">
                @foreach($recentSessions as $session)
                    <div class="gaming-session-item">
                        <span class="gaming-session-item__date">{{ $session->started_at->format('d M Y, H:i') }}</span>
                        <span class="gaming-session-item__duration">{{ $session->formatted_duration }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $recentSessions->links() }}
            </div>
        @endif
    </x-ui.card>

</x-layouts.app>
