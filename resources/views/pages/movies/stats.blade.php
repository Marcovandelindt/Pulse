<x-layouts.app title="Movie Stats">

    <x-layout.page-header title="Movie Statistics">
        <x-slot:actions>
            <a href="{{ route('movies.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Hero stat --}}
    @if ($totalHours > 0)
        <div class="media-stats-hero mb-6">
            <div class="media-stats-hero__value">{{ number_format($totalHours, 1) }}h</div>
            <div class="media-stats-hero__label">total watch time</div>
        </div>
    @endif

    {{-- Overview grid --}}
    <div class="health-stats-grid mb-6">
        <x-stats.stat-card label="Movies" :value="$totalMovies" />
        <x-stats.stat-card label="Total watches" :value="$totalWatches" />
        <x-stats.stat-card label="Avg rating" :value="$averageRating ? number_format((float) $averageRating, 1) . '/10' : '—'" />
        <x-stats.stat-card label="Unique titles" :value="$uniqueMovies" />
    </div>

    {{-- First & Last watch --}}
    @if ($firstWatch || $lastWatch)
        <div class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-2">
            @if ($firstWatch)
                <x-ui.card title="First Watch">
                    <div class="flex items-center gap-4">
                        @if ($firstWatch->movie->poster_url)
                            <img src="{{ $firstWatch->movie->poster_url }}" alt="{{ $firstWatch->movie->title }}"
                                 style="width:3.5rem;border-radius:var(--radius-sm);flex-shrink:0;">
                        @endif
                        <div>
                            <a href="{{ route('movies.show', $firstWatch->movie) }}" class="font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-brand-light)]">
                                {{ $firstWatch->movie->title }}
                            </a>
                            <div class="text-sm text-[var(--color-text-muted)] mt-1">{{ $firstWatch->formattedWatchedAt() }}</div>
                        </div>
                    </div>
                </x-ui.card>
            @endif
            @if ($lastWatch)
                <x-ui.card title="Most Recent">
                    <div class="flex items-center gap-4">
                        @if ($lastWatch->movie->poster_url)
                            <img src="{{ $lastWatch->movie->poster_url }}" alt="{{ $lastWatch->movie->title }}"
                                 style="width:3.5rem;border-radius:var(--radius-sm);flex-shrink:0;">
                        @endif
                        <div>
                            <a href="{{ route('movies.show', $lastWatch->movie) }}" class="font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-brand-light)]">
                                {{ $lastWatch->movie->title }}
                            </a>
                            <div class="text-sm text-[var(--color-text-muted)] mt-1">{{ $lastWatch->formattedWatchedAt() }}</div>
                        </div>
                    </div>
                </x-ui.card>
            @endif
        </div>
    @endif

    {{-- Most watched --}}
    <x-ui.card title="Most watched" class="mb-6">
        @if ($mostWatched->isEmpty())
            <x-ui.empty-state title="No data yet" />
        @else
            <div class="media-cast media-cast--grid">
                @foreach ($mostWatched as $movie)
                    <a href="{{ route('movies.show', $movie) }}" class="media-cast__member">
                        @if ($movie->poster_url)
                            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="media-cast__photo">
                        @else
                            <div class="media-cast__photo media-cast__photo--empty"></div>
                        @endif
                        <div class="media-cast__name">{{ $movie->title }}</div>
                        <div class="media-cast__role">{{ $movie->watches_count }}×</div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Genre breakdown --}}
        <x-ui.card title="By genre">
            @if (empty($genreStats))
                <x-ui.empty-state title="No data yet" />
            @else
                <div class="flex flex-col gap-2">
                    @php $maxGenre = max($genreStats) ?: 1; @endphp
                    @foreach (array_slice($genreStats, 0, 10, true) as $genre => $count)
                        <div class="flex items-center gap-3">
                            <div class="text-sm text-[var(--color-text-muted)]" style="width:7rem;flex-shrink:0;">{{ $genre }}</div>
                            <div style="flex:1;height:0.5rem;background:var(--color-bg-tertiary);border-radius:999px;overflow:hidden;">
                                <div style="width:{{ round(($count / $maxGenre) * 100) }}%;height:100%;background:var(--color-brand);border-radius:999px;"></div>
                            </div>
                            <div class="text-sm text-[var(--color-text-muted)] text-right" style="width:2rem;">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Recent by day --}}
        <x-ui.card title="Recent activity">
            @if (empty($recentByDay))
                <x-ui.empty-state title="No watches yet" />
            @else
                <div class="flex flex-col gap-3">
                    @foreach ($recentByDay as $day)
                        <div>
                            <div class="text-xs text-[var(--color-text-muted)] mb-1">{{ $day['date'] }}</div>
                            <div class="text-sm text-[var(--color-text-primary)]">{{ $day['titles'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>

</x-layouts.app>

