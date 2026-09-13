<x-layouts.app title="Play Next — PlayStation">

    <x-layout.page-header title="Play Next">
        <x-slot:actions>
            <a href="{{ route('playstation.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
            <a href="{{ route('playstation.sessions.index') }}" class="btn btn--secondary btn--sm">Sessions</a>
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← All Games</a>
        </x-slot:actions>
    </x-layout.page-header>

    @php
        $completedCount = $liked->where('backlog_status', \App\Enums\BacklogStatus::Completed)->count();
        $favCount       = $liked->where('backlog_status', '!=', \App\Enums\BacklogStatus::Completed)
                                ->whereNotNull('user_rating')->count();
    @endphp

    <p class="mb-6 text-sm" style="color: var(--color-text-muted)">
        Based on
        @if($completedCount)
            <span style="color: var(--color-text-primary)">{{ $completedCount }} completed {{ Str::plural('game', $completedCount) }}</span>
        @endif
        @if($completedCount && $favCount)
            and
        @endif
        @if($favCount)
            <span style="color: var(--color-text-primary)">{{ $favCount }} {{ Str::plural('favourite', $favCount) }} (rated 7+)</span>
        @endif
        @if(! $completedCount && ! $favCount)
            your library — complete games or rate them 7+ to get tailored suggestions.
        @endif
        .
    </p>

    @if($recommended->isEmpty() && $unmatched->isEmpty())
        <x-ui.empty-state
            title="Nothing left to play"
            description="All your games are completed, dropped, or continuously played."
        />
    @else

        {{-- Recommended section --}}
        @if($recommended->isNotEmpty())
            <div class="mb-8">
                <h2 class="text-base font-semibold mb-4" style="color: var(--color-text-primary)">
                    Recommended
                    <span class="font-normal text-sm ml-1" style="color: var(--color-text-muted)">({{ $recommended->count() }})</span>
                </h2>

                <div class="gaming-grid">
                    @foreach($recommended as $row)
                        @php
                            $game      = $row['game'];
                            $matches   = $row['matching_categories'];
                            $fallbacks = ['ps1.jpg','ps2.webp','ps3.jpg','ps4.jpg','ps5.jpg'];
                            $coverUrl  = $game->image_url ?? '/images/playstation/' . $fallbacks[$game->id % 5];
                            $hours     = round(($game->psn_total_minutes > 0 ? $game->psn_total_minutes : ($game->play_sessions_sum_duration_minutes ?? 0)) / 60, 1);
                        @endphp
                        <div class="gaming-card">
                            <div class="gaming-card__cover-wrap">
                                <a href="{{ route('playstation.show', $game) }}" class="gaming-card__cover-link">
                                    <img src="{{ $coverUrl }}" alt="{{ $game->label }}" class="gaming-card__cover">
                                </a>
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
                                    <span>{{ $hours > 0 ? $hours . 'h played' : 'Not started' }}</span>
                                </div>
                                @if($matches->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @foreach($matches as $category)
                                            <span class="inline-block px-1.5 py-0.5 rounded text-xs font-medium"
                                                  style="background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.25);">
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Unmatched backlog --}}
        @if($unmatched->isNotEmpty())
            <div>
                <h2 class="text-base font-semibold mb-1" style="color: var(--color-text-primary)">
                    Rest of your backlog
                    <span class="font-normal text-sm ml-1" style="color: var(--color-text-muted)">({{ $unmatched->count() }})</span>
                </h2>
                <p class="text-xs mb-4" style="color: var(--color-text-muted)">
                    No category overlap with your completed or liked games — add categories to these games to get better suggestions.
                </p>

                <div class="gaming-grid">
                    @foreach($unmatched as $row)
                        @php
                            $game      = $row['game'];
                            $fallbacks = ['ps1.jpg','ps2.webp','ps3.jpg','ps4.jpg','ps5.jpg'];
                            $coverUrl  = $game->image_url ?? '/images/playstation/' . $fallbacks[$game->id % 5];
                            $hours     = round(($game->psn_total_minutes > 0 ? $game->psn_total_minutes : ($game->play_sessions_sum_duration_minutes ?? 0)) / 60, 1);
                        @endphp
                        <div class="gaming-card">
                            <div class="gaming-card__cover-wrap">
                                <a href="{{ route('playstation.show', $game) }}" class="gaming-card__cover-link">
                                    <img src="{{ $coverUrl }}" alt="{{ $game->label }}" class="gaming-card__cover">
                                </a>
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
                                    <span>{{ $hours > 0 ? $hours . 'h played' : 'Not started' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    @endif

</x-layouts.app>
