<x-layouts.app title="TV Stats">

    <x-layout.page-header title="TV Statistics">
        <x-slot:actions>
            <a href="{{ route('tv.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Hero stat --}}
    @if ($totalHours > 0)
        <div class="media-stats-hero mb-6">
            <div class="media-stats-hero__value">{{ number_format($totalHours, 1) }}h</div>
            <div class="media-stats-hero__label">total watch time · {{ number_format($episodesWatched) }} episodes</div>
        </div>
    @endif

    {{-- Overview --}}
    <div class="health-stats-grid mb-6">
        <x-stats.stat-card label="Shows" :value="$totalSeries" />
        <x-stats.stat-card label="Episodes watched" :value="number_format($episodesWatched)" />
        <x-stats.stat-card label="Completed" :value="$completed" />
        <x-stats.stat-card label="In progress" :value="$inProgress" />
    </div>

    {{-- Status breakdown --}}
    <x-ui.card title="Status breakdown" class="mb-6">
        <div class="flex gap-8">
            <div>
                <div class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $completed }}</div>
                <div class="text-sm text-[var(--color-text-muted)] mt-1">Completed</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $inProgress }}</div>
                <div class="text-sm text-[var(--color-text-muted)] mt-1">In progress</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $notStarted }}</div>
                <div class="text-sm text-[var(--color-text-muted)] mt-1">Not started</div>
            </div>
        </div>
    </x-ui.card>

    {{-- First & Last watch --}}
    @if ($firstWatch || $lastWatch)
        <div class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-2">
            @if ($firstWatch)
                <x-ui.card title="First Watch">
                    <div class="flex items-center gap-4">
                        @php $fw = $firstWatch->episode->season->series; @endphp
                        @if ($fw->poster_url)
                            <img src="{{ $fw->poster_url }}" alt="{{ $fw->name }}"
                                 style="width:3.5rem;border-radius:var(--radius-sm);flex-shrink:0;">
                        @endif
                        <div>
                            <a href="{{ route('tv.show', $fw) }}" class="font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-brand-light)]">
                                {{ $fw->name_en ?? $fw->name }}
                            </a>
                            <div class="text-sm text-[var(--color-text-muted)] mt-1">{{ $firstWatch->episode->name }}</div>
                            <div class="text-sm text-[var(--color-text-muted)]">{{ $firstWatch->formattedWatchedAt() }}</div>
                        </div>
                    </div>
                </x-ui.card>
            @endif
            @if ($lastWatch)
                <x-ui.card title="Most Recent">
                    <div class="flex items-center gap-4">
                        @php $lw = $lastWatch->episode->season->series; @endphp
                        @if ($lw->poster_url)
                            <img src="{{ $lw->poster_url }}" alt="{{ $lw->name }}"
                                 style="width:3.5rem;border-radius:var(--radius-sm);flex-shrink:0;">
                        @endif
                        <div>
                            <a href="{{ route('tv.show', $lw) }}" class="font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-brand-light)]">
                                {{ $lw->name_en ?? $lw->name }}
                            </a>
                            <div class="text-sm text-[var(--color-text-muted)] mt-1">{{ $lastWatch->episode->name }}</div>
                            <div class="text-sm text-[var(--color-text-muted)]">{{ $lastWatch->formattedWatchedAt() }}</div>
                        </div>
                    </div>
                </x-ui.card>
            @endif
        </div>
    @endif

    {{-- Top series --}}
    <x-ui.card title="Most watched series" class="mb-6">
        @if ($topSeries->isEmpty())
            <x-ui.empty-state title="No data yet" />
        @else
            <div class="media-cast media-cast--grid">
                @foreach ($topSeries as $show)
                    <a href="{{ route('tv.show', $show) }}" class="media-cast__member">
                        @if ($show->poster_url)
                            <img src="{{ $show->poster_url }}" alt="{{ $show->name }}" class="media-cast__photo">
                        @else
                            <div class="media-cast__photo media-cast__photo--empty"></div>
                        @endif
                        <div class="media-cast__name">{{ $show->name_en ?? $show->name }}</div>
                        <div class="media-cast__role">{{ $show->episodes_watched }} ep</div>
                        <div class="media-cast__episodes">{{ round($show->completion_percentage) }}%</div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">

        {{-- Genre breakdown --}}
        <x-ui.card title="By genre">
            @if (empty($genreStats))
                <x-ui.empty-state title="No data yet" />
            @else
                <div class="flex flex-col gap-2">
                    @php $maxGenre = max($genreStats) ?: 1; @endphp
                    @foreach (array_slice($genreStats, 0, 10, true) as $genre => $count)
                        <div class="flex items-center gap-3">
                            <div class="text-sm text-[var(--color-text-muted)]" style="width:8rem;flex-shrink:0;">{{ $genre }}</div>
                            <div style="flex:1;height:0.5rem;background:var(--color-bg-tertiary);border-radius:999px;overflow:hidden;">
                                <div style="width:{{ round(($count / $maxGenre) * 100) }}%;height:100%;background:var(--color-brand);border-radius:999px;"></div>
                            </div>
                            <div class="text-sm text-[var(--color-text-muted)] text-right" style="width:2rem;">{{ $count }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Monthly history --}}
        <x-ui.card title="Episodes per month">
            @if (empty($monthlyHistory))
                <x-ui.empty-state title="No data yet" />
            @else
                @php $maxEps = max(array_column($monthlyHistory, 'episodes')) ?: 1; @endphp
                <div class="health-bar-chart">
                    @foreach (array_reverse($monthlyHistory) as $row)
                        <div class="health-bar-chart__column">
                            <div class="health-bar-chart__bar-wrap">
                                <div class="health-bar-chart__bar"
                                     style="height: {{ round(($row['episodes'] / $maxEps) * 100) }}%"
                                     title="{{ $row['month'] }}: {{ $row['episodes'] }} episodes"></div>
                            </div>
                            <div class="health-bar-chart__label">
                                {{ \Carbon\Carbon::createFromFormat('F Y', $row['month'])->format('M') }}
                            </div>
                            <div class="health-bar-chart__value">{{ $row['episodes'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>

    {{-- In-progress series (completion progress bars) --}}
    @if ($inProgressSeries->isNotEmpty())
        <x-ui.card title="In progress">
            <div class="flex flex-col gap-4">
                @foreach ($inProgressSeries as $show)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <a href="{{ route('tv.show', $show) }}" class="text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-brand-light)]">
                                {{ $show->name_en ?? $show->name }}
                            </a>
                            <span class="text-xs text-[var(--color-text-muted)]">
                                {{ $show->episodes_watched }} / {{ $show->number_of_episodes }}
                            </span>
                        </div>
                        <div style="height:0.375rem;background:var(--color-bg-tertiary);border-radius:999px;overflow:hidden;">
                            <div style="width:{{ min(100, round($show->completion_percentage)) }}%;height:100%;background:linear-gradient(90deg, var(--color-brand), oklch(0.75 0.14 260));border-radius:999px;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

</x-layouts.app>
