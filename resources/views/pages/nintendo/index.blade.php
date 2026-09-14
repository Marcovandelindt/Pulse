<x-layouts.app title="Nintendo Switch">
    <div class="page-header">
        <div class="page-header__left">
            <h1 class="page-header__title">Nintendo Switch</h1>
        </div>
        <div class="page-header__actions">
            <a href="{{ route('nintendo.sessions') }}" class="btn btn--secondary btn--sm">Sessions</a>
            <a href="{{ route('nintendo.import') }}" class="btn btn--secondary btn--sm">Import screenshot</a>
            <a href="{{ route('nintendo.create') }}" class="btn btn--primary btn--sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem;height:1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add game
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert--success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 mb-6">
        <x-stats.stat-card
            label="Games"
            :value="$totalGames ?: '—'"
            icon="puzzle-piece"
        />
        <x-stats.stat-card
            label="Total playtime"
            :value="$totalMinutes > 0 ? round($totalMinutes / 60) . 'h' : '—'"
            icon="clock"
        />
        <x-stats.stat-card
            label="Sessions (30d)"
            :value="$recentActivity ?: '—'"
            icon="calendar"
        />
    </div>

    {{-- Games grid --}}
    @if($games->isEmpty())
        <x-ui.empty-state
            title="No Switch games yet"
            description="Add your first game and start logging play sessions."
            icon="puzzle-piece"
        >
            <x-slot name="action">
                <a href="{{ route('nintendo.create') }}" class="btn btn--primary btn--sm">Add game</a>
            </x-slot>
        </x-ui.empty-state>
    @else
        <div class="gaming-grid">
            @foreach($games as $game)
                <a href="{{ route('nintendo.show', $game) }}" class="gaming-card" style="text-decoration:none;">
                    <div class="gaming-card__cover-wrap">
                        @if($game->image_url)
                            <img
                                src="{{ $game->image_url }}"
                                alt="{{ $game->name }}"
                                class="gaming-card__cover"
                                loading="lazy"
                            >
                        @else
                            <div class="gaming-card__cover--empty">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:2rem;height:2rem;color:var(--color-text-muted);">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.401.604-.401.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <div class="gaming-card__body">
                        <span class="gaming-card__title">{{ $game->name }}</span>
                        <div class="gaming-card__stats">
                            <span>{{ $game->formatted_hours ?: '0m' }}</span>
                            @if($game->last_played_at)
                                <span>·</span>
                                <span>{{ $game->last_played_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.app>
