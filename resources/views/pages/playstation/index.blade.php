<x-layouts.app title="PlayStation">

<div x-data="playstationIndex({ sleepItems: @js($sleepItems) })">

    <x-layout.page-header title="PlayStation">
        <x-slot:actions>
            <button @click="enterSleep()" class="btn btn--secondary btn--sm" x-show="sleepItems.length > 0" style="display:none;">☾ Sleep</button>
            <a href="{{ route('playstation.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
            <a href="{{ route('playstation.sessions.index') }}" class="btn btn--secondary btn--sm">Sessions</a>
            <a href="{{ route('playstation.categories.index') }}" class="btn btn--secondary btn--sm">Categories</a>
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

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 mb-6">
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
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">

        <div class="lg:col-span-2">

            <div class="media-toolbar mb-4">
                <div class="flex gap-2 flex-wrap items-center">
                    <form method="GET" action="{{ route('playstation.index') }}" class="flex items-center gap-2">
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="platform" value="{{ $platform }}">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search games…"
                            class="form-input"
                            style="max-width: 200px;"
                        >
                        @if($search)
                            <a href="{{ route('playstation.index', array_filter(['sort' => $sort, 'platform' => $platform ?: null])) }}"
                               class="btn btn--secondary btn--sm">Clear</a>
                        @endif
                    </form>
                    @foreach(['', 'PS5', 'PS4', 'PS3', 'PSVITA'] as $p)
                        <a
                            href="{{ route('playstation.index', array_filter(['platform' => $p ?: null, 'sort' => $sort, 'search' => $search ?: null])) }}"
                            class="btn btn--sm {{ ($platform === $p || ($p === '' && ! $platform)) ? 'btn--primary' : 'btn--secondary' }}"
                        >{{ $p ?: 'All' }}</a>
                    @endforeach
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm" style="color: var(--color-text-muted)">Sort:</span>
                    @foreach(['hours' => 'Most Played', 'name' => 'Name', 'last_played' => 'Last Played', 'completion' => 'Completion'] as $key => $label)
                        <a
                            href="{{ route('playstation.index', array_filter(['sort' => $key, 'platform' => $platform ?: null, 'search' => $search ?: null])) }}"
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
                        <div class="gaming-card" x-data="{ isFavorite: {{ $game->is_favorite ? 'true' : 'false' }} }">
                            <div class="gaming-card__cover-wrap">
                                @php
                                    $fallbacks = ['ps1.jpg','ps2.webp','ps3.jpg','ps4.jpg','ps5.jpg'];
                                    $coverUrl  = $game->image_url ?? '/images/playstation/' . $fallbacks[$game->id % 5];
                                @endphp
                                <a href="{{ route('playstation.show', $game) }}" class="gaming-card__cover-link">
                                    <img src="{{ $coverUrl }}" alt="{{ $game->label }}" class="gaming-card__cover">
                                </a>
                                @if($game->completion_percentage > 0)
                                    <span class="gaming-card__completion-badge">{{ number_format($game->completion_percentage, 0) }}%</span>
                                @endif
                                <button
                                    class="gaming-card__favorite"
                                    :class="{ 'gaming-card__favorite--active': isFavorite }"
                                    @click="
                                        isFavorite = !isFavorite;
                                        fetch('{{ route('playstation.favorite', $game) }}', {
                                            method: 'PATCH',
                                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                        });
                                    "
                                    :title="isFavorite ? 'Remove from favorites' : 'Add to favorites'"
                                >★</button>
                            </div>
                            <div class="gaming-card__body">
                                <a href="{{ route('playstation.show', $game) }}" class="gaming-card__title">{{ $game->label }}</a>
                                <div class="gaming-card__meta">
                                    <span class="gaming-platform-badge" style="background: {{ $game->platformColor() }}">{{ $game->platform }}</span>
                                    @if($game->backlog_status)
                                        <x-ui.badge :color="$game->backlog_status->color()">{{ $game->backlog_status->label() }}</x-ui.badge>
                                    @endif
                                </div>
                                <div class="gaming-card__stats">
                                    <span>{{ number_format($game->calculated_hours, 1) }}h</span>
                                    @if($game->trophy_list_count > 0)
                                        <span>·</span>
                                        <span>🏆 {{ $game->earned_trophy_count }}/{{ $game->trophy_list_count }}</span>
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

            @if($games->hasPages())
                <div class="mt-6">
                    {{ $games->links() }}
                </div>
            @endif

        </div>

        <div>
            @if($currentGame)
                <x-ui.card title="Now gaming" class="card--flush mb-6">
                    <x-playstation.now-gaming :game="$currentGame" />
                </x-ui.card>
            @endif

            @if($recentSessions->isNotEmpty())
                <x-ui.card title="Recent Sessions">
                    <div class="gaming-session-list">
                        @foreach($recentSessions as $session)
                            <div class="gaming-session-item">
                                <div class="gaming-session-item__game">
                                    <a href="{{ route('playstation.show', $session->game) }}" class="gaming-session-item__title">
                                        {{ $session->game->label }}
                                    </a>
                                    <span class="gaming-platform-badge" style="background: {{ $session->game->platformColor() }}">
                                        {{ $session->game->platform }}
                                    </span>
                                </div>
                                <div class="gaming-session-item__meta">
                                    <span class="gaming-session-item__duration">{{ $session->formatted_duration }}</span>
                                    <span class="gaming-session-item__date">{{ $session->started_at->format('d M, H:i') }} – {{ $session->end_time->format('H:i') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @else
                <x-ui.card title="Recent Sessions">
                    <p class="text-sm" style="color: var(--color-text-muted)">
                        No sessions yet. Run <code>playstation:sync-sessions</code> to import.
                    </p>
                </x-ui.card>
            @endif
        </div>

    </div>

    {{-- Sleep mode --}}
    <div class="sleep-mode" x-show="sleepOpen" style="display:none;">
        <div class="sleep-mode__backdrop"
             :style="sleepItem ? `background-image: url('${sleepItem.image_url}')` : ''"
             x-effect="sleepIndex; $el.style.animation = 'none'; $el.offsetHeight; $el.style.animation = ''">
        </div>

        <div class="sleep-mode__fade" :class="{ 'sleep-mode__fade--active': sleepTransitioning }"></div>

        <button class="sleep-mode__close" @click="exitSleep()">✕</button>

        <button class="sleep-mode__nav sleep-mode__nav--prev" @click="_sleepPrev()">‹</button>
        <button class="sleep-mode__nav sleep-mode__nav--next" @click="_sleepNext()">›</button>

        <div class="sleep-mode__clock">
            <div class="sleep-mode__clock-time" x-text="clockTime"></div>
            <div class="sleep-mode__clock-date" x-text="clockDate"></div>
        </div>

        <div class="sleep-mode__content">
            <div class="sleep-mode__poster-wrap">
                <img :src="sleepItem?.image_url" :alt="sleepItem?.title" class="sleep-mode__poster">
            </div>
            <div class="sleep-mode__info">
                <div class="sleep-mode__title" x-text="sleepItem?.title"></div>
                <div class="sleep-mode__meta" x-text="sleepItem?.platform"></div>
                <div class="sleep-mode__stats">
                    <div>
                        <div class="sleep-mode__stat-label">Hours played</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.hours + 'h'"></div>
                    </div>
                    <template x-if="sleepItem?.completion > 0">
                        <div>
                            <div class="sleep-mode__stat-label">Completion</div>
                            <div class="sleep-mode__stat" x-text="sleepItem?.completion + '%'"></div>
                            <div class="sleep-mode__bar-wrap">
                                <div class="sleep-mode__bar" :style="`width: ${sleepItem?.completion}%`"></div>
                            </div>
                        </div>
                    </template>
                    <template x-if="sleepItem?.last_played">
                        <div>
                            <div class="sleep-mode__stat-label">Last played</div>
                            <div class="sleep-mode__stat" x-text="sleepItem?.last_played"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="sleep-mode__counter" x-text="`${sleepIndex + 1} / ${sleepItems.length}`"></div>

        <div class="sleep-progress">
            <div
                class="sleep-progress__fill"
                x-effect="sleepIndex; $el.style.animation = 'none'; $el.offsetWidth; $el.style.animation = ''"
            ></div>
        </div>
    </div>

</div>

</x-layouts.app>
