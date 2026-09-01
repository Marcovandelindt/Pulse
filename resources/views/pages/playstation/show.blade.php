<x-layouts.app :title="$game->label">

    <div x-data="{
        isFavorite: {{ $game->is_favorite ? 'true' : 'false' }},
        toggleFavorite() {
            this.isFavorite = !this.isFavorite;
            fetch('{{ route('playstation.favorite', $game) }}', {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            });
        }
    }">

    <x-layout.page-header :title="$game->label">
        <x-slot:actions>
            <button
                @click="toggleFavorite()"
                class="btn btn--secondary btn--sm"
                :class="{ 'btn--favorite-active': isFavorite }"
                x-text="isFavorite ? '★ Favorited' : '☆ Favorite'"
            ></button>
            <form method="POST" action="{{ route('playstation.fetch-trophies', $game) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn--secondary btn--sm">🏆 Fetch Trophies</button>
            </form>
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
    @if(session('error'))
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
             style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25);">
            {{ session('error') }}
        </div>
    @endif

    @php
        $fallbacks = ['ps1.jpg','ps2.webp','ps3.jpg','ps4.jpg','ps5.jpg'];
        $coverUrl  = $game->image_url ?? '/images/playstation/' . $fallbacks[$game->id % 5];
    @endphp

    <div class="gaming-hero mb-6">
        <div class="gaming-hero__cover">
            <img src="{{ $coverUrl }}" alt="{{ $game->label }}" class="gaming-hero__img">
        </div>
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
            <h2 class="text-2xl font-bold mb-1" style="color: var(--color-text-primary)">{{ $game->label }}</h2>
            @if($game->play_mode?->isNotEmpty())
                <div class="flex flex-wrap gap-1 mb-2">
                    @foreach($game->play_mode as $mode)
                        <x-ui.badge color="gray">{{ $mode->label() }}</x-ui.badge>
                    @endforeach
                </div>
            @endif
            @if($game->categories->isNotEmpty())
                <div class="flex flex-wrap gap-1 mb-3">
                    @foreach($game->categories as $category)
                        <x-ui.badge color="brand">{{ $category->name }}</x-ui.badge>
                    @endforeach
                </div>
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

    @php
        $trophyEarned = $game->trophyList->where('is_earned', true)->count();
        $trophyTotal  = $game->trophyList->count();
    @endphp

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
        <x-stats.stat-card
            label="Hours Played"
            :value="number_format($game->calculated_hours, 1) . 'h'"
            :subtitle="$game->psn_total_minutes ? number_format($game->tracked_hours, 1) . 'h tracked' : null"
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
            label="Trophies"
            :value="$trophyTotal ? $trophyEarned . ' / ' . $trophyTotal : '—'"
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

    @if($game->trophyList->isNotEmpty())
        @php
            $trophyTypes = [
                'platinum' => ['label' => 'Platinum', 'emoji' => '💎'],
                'gold'     => ['label' => 'Gold',     'emoji' => '🥇'],
                'silver'   => ['label' => 'Silver',   'emoji' => '🥈'],
                'bronze'   => ['label' => 'Bronze',   'emoji' => '🥉'],
            ];
        @endphp
        <div x-data="{ search: '', earnedCount: {{ $trophyEarned }} }" @trophy-toggled="earnedCount += $event.detail.delta">
        <x-ui.card class="mb-6">
            <x-slot:title>
                Trophies
                <span style="color: var(--color-text-muted); font-weight: 400; font-size: 0.875rem;">
                    <span x-text="earnedCount">{{ $trophyEarned }}</span> / {{ $trophyTotal }}
                </span>
            </x-slot:title>

            <div class="trophy-search-wrap">
                <input
                    type="search"
                    x-model="search"
                    placeholder="Search trophies…"
                    class="trophy-search"
                >
            </div>

            @foreach($trophyTypes as $type => $meta)
                @php
                    $group   = $game->trophyList->where('type', $type);
                    $earned  = $group->where('is_earned', true)->sortByDesc('earned_at');
                    $locked  = $group->where('is_earned', false);
                    $ordered = $earned->merge($locked);
                @endphp
                @if($group->isNotEmpty())
                    <div class="trophy-group">
                        <div class="trophy-group__label">
                            {{ $meta['emoji'] }} {{ $meta['label'] }}
                            <span style="font-weight: 400; opacity: 0.6;">
                                {{ $earned->count() }} / {{ $group->count() }}
                            </span>
                        </div>
                        <div class="trophy-grid">
                            @foreach($ordered as $trophy)
                                <div
                                    class="trophy-item trophy-item--toggleable"
                                    x-data="{
                                        name: '{{ addslashes(strtolower($trophy->name)) }}',
                                        earned: {{ $trophy->is_earned ? 'true' : 'false' }},
                                        showDetail: false,
                                        toggle() {
                                            fetch('{{ route('playstation.trophies.toggle', $trophy) }}', {
                                                method: 'PATCH',
                                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                            })
                                            .then(r => r.json())
                                            .then(data => {
                                                this.earned = data.is_earned;
                                                this.$dispatch('trophy-toggled', { delta: data.is_earned ? 1 : -1 });
                                            });
                                        }
                                    }"
                                    x-show="!search || name.includes(search.toLowerCase())"
                                    :class="earned ? 'trophy-item--earned' : 'trophy-item--locked'"
                                    @click="toggle()"
                                    @mouseenter="showDetail = true"
                                    @mouseleave="showDetail = false"
                                >
                                    @if($trophy->icon_url)
                                        <img src="{{ $trophy->icon_url }}" alt="" class="trophy-item__icon">
                                    @else
                                        <div class="trophy-item__icon--placeholder" style="font-size: 1.25rem; color: {{ $trophy->typeColor() }}">
                                            {{ $meta['emoji'] }}
                                        </div>
                                    @endif
                                    <div class="trophy-item__info">
                                        <div class="trophy-item__name">{{ $trophy->name }}</div>
                                        @if($trophy->detail)
                                            <div class="trophy-item__detail">{{ $trophy->detail }}</div>
                                        @endif
                                        @if($trophy->rarityLabel())
                                            <div class="trophy-item__rarity" style="color: {{ $trophy->rarityColor() }}">
                                                {{ $trophy->rarityLabel() }}
                                                @if($trophy->earned_rate !== null)
                                                    <span class="trophy-item__rarity-rate">· {{ number_format((float) $trophy->earned_rate, 1) }}%</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($trophy->earned_at)
                                            <div class="trophy-item__earned-at">{{ $trophy->earned_at->format('l, d M Y, H:i') }}</div>
                                        @endif
                                    </div>
                                    <div class="trophy-item__badge" style="color: {{ $trophy->typeColor() }}" x-show="earned">✓</div>
                                    @if($trophy->detail)
                                        <div class="trophy-item__tooltip" x-show="showDetail" x-cloak>{{ $trophy->detail }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </x-ui.card>
        </div>
    @endif

    @if($game->tracks->isNotEmpty())
        <x-ui.card title="Soundtrack" class="mb-6">
            <div class="space-y-2">
                @foreach($game->tracks as $track)
                    <div class="flex items-center gap-3 py-1">
                        @if($track->album?->image_url)
                            <img src="{{ $track->album->image_url }}" alt=""
                                 style="width: 2.25rem; height: 2.25rem; object-fit: cover; border-radius: var(--radius-sm); flex-shrink: 0;">
                        @endif
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('music.tracks.show', $track) }}"
                               class="text-sm font-medium truncate block hover:underline"
                               style="color: var(--color-text-primary)">{{ $track->title }}</a>
                            <div class="text-xs truncate" style="color: var(--color-text-muted)">
                                {{ $track->artists->pluck('name')->implode(', ') }}
                            </div>
                        </div>
                        <span class="text-xs flex-shrink-0" style="color: var(--color-text-muted)">
                            {{ $track->formatted_duration }}
                        </span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <x-ui.card title="Sessions">
        @if($recentSessions->isEmpty())
            <p class="text-sm" style="color: var(--color-text-muted)">No sessions recorded yet.</p>
        @else
            <div class="gaming-session-list">
                @foreach($recentSessions as $session)
                    <div class="gaming-session-item">
                        <span class="gaming-session-item__date">{{ $session->started_at->format('d M Y, H:i') }} – {{ $session->end_time->format('H:i') }}</span>
                        <span class="gaming-session-item__duration">{{ $session->formatted_duration }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $recentSessions->links() }}
            </div>
        @endif
    </x-ui.card>

    </div>

</x-layouts.app>
