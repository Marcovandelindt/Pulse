<x-layouts.app title="PlayStation">

    <x-layout.page-header title="PlayStation">
        <x-slot:actions>
            <a href="{{ route('playstation.sessions') }}" class="btn btn--secondary btn--sm">Sessions</a>
            <a href="{{ route('playstation.create') }}" class="btn btn--secondary btn--sm">+ Add game</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
             style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
             style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25);">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
        <x-stats.stat-card
            label="Total Hours"
            :value="number_format($totalHours, 1) . 'h'"
            icon="clock"
        />
        <x-stats.stat-card
            label="Games"
            :value="$totalGames"
            icon="play"
        />
        <x-stats.stat-card
            label="Sessions"
            :value="number_format($totalSessions)"
            icon="play"
        />
        <x-stats.stat-card
            label="Trophies"
            :value="number_format($totalTrophies)"
            icon="heart"
        />
    </div>

    @if($recentSessions->isNotEmpty())
        <x-ui.card title="Recent Sessions" class="mb-6">
            <div class="gaming-session-list">
                @foreach($recentSessions as $session)
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
                            <span class="gaming-session-item__date">{{ $session->started_at->format('d M Y') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <div
        x-data="{ platform: '{{ $platform }}', sort: '{{ $sort }}' }"
        class="mb-4 flex flex-wrap gap-3 items-center justify-between"
    >
        <div class="flex gap-2 flex-wrap">
            @foreach(['', 'PS5', 'PS4', 'PS3', 'PSVITA'] as $p)
                <a
                    href="{{ route('playstation.index', array_filter(['platform' => $p ?: null, 'sort' => $sort])) }}"
                    class="btn btn--sm {{ ($platform === $p || ($p === '' && ! $platform)) ? 'btn--primary' : 'btn--secondary' }}"
                >
                    {{ $p ?: 'All' }}
                </a>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm" style="color: var(--color-text-muted)">Sort:</span>
            @foreach(['hours' => 'Hours', 'name' => 'Name', 'last_played' => 'Last Played', 'completion' => 'Completion', 'trophies' => 'Trophies'] as $key => $label)
                <a
                    href="{{ route('playstation.index', array_filter(['sort' => $key, 'platform' => $platform ?: null])) }}"
                    class="btn btn--sm {{ $sort === $key ? 'btn--primary' : 'btn--secondary' }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if($games->isEmpty())
        <x-ui.empty-state title="No games yet" description="Add your first game or sync from PS-Timetracker.">
            <x-slot:action>
                <a href="{{ route('playstation.create') }}" class="btn btn--primary btn--sm">+ Add Game</a>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="gaming-grid">
            @foreach($games as $game)
                <div class="gaming-card">
                    <a href="{{ route('playstation.show', $game) }}" class="gaming-card__cover-link">
                        @if($game->image_url)
                            <img src="{{ $game->image_url }}" alt="{{ $game->name }}" class="gaming-card__cover">
                        @else
                            <div class="gaming-card__cover gaming-card__cover--empty">
                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:2rem;height:2rem;opacity:0.3">
                                    <path d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                                </svg>
                            </div>
                        @endif
                        @if($game->completion_percentage > 0)
                            <span class="gaming-card__completion-badge">{{ number_format($game->completion_percentage, 0) }}%</span>
                        @endif
                    </a>
                    <div class="gaming-card__body">
                        <a href="{{ route('playstation.show', $game) }}" class="gaming-card__title">{{ $game->name }}</a>
                        <div class="gaming-card__meta">
                            <span class="gaming-platform-badge" style="background: {{ $game->platformColor() }}">{{ $game->platform }}</span>
                            @if($game->backlog_status)
                                <x-ui.badge :color="$game->backlog_status->color()">{{ $game->backlog_status->label() }}</x-ui.badge>
                            @endif
                        </div>
                        <div class="gaming-card__stats">
                            <span>{{ $game->formatted_hours }}</span>
                            @if($game->trophies > 0)
                                <span>🏆 {{ $game->trophies }}</span>
                            @endif
                        </div>
                        @if($game->completion_percentage > 0)
                            <div class="gaming-card__progress">
                                <div class="gaming-card__progress-bar" style="width: {{ min(100, $game->completion_percentage) }}%"></div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-layouts.app>
