<x-layouts.app title="Music Stats">

    <x-layout.page-header title="Music Statistics">
        <x-slot:actions>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Hero --}}
    @if ($totalListens > 0)
        <div class="media-stats-hero mb-6">
            <div class="media-stats-hero__value">{{ $totalListens }}</div>
            <div class="media-stats-hero__label">total listens</div>
            @if ($totalHours > 0)
                <div class="media-stats-hero__sub">≈ {{ number_format($totalHours, 1) }}h of music</div>
            @endif
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="health-stats-grid mb-6">
        <x-stats.stat-card label="Albums" :value="$totalAlbums" />
        <x-stats.stat-card label="Artists" :value="$uniqueArtists" />
        <x-stats.stat-card label="Avg rating" :value="$averageRating ? number_format((float) $averageRating, 1) . '/10' : '—'" />
        <x-stats.stat-card label="Total listens" :value="$totalListens" />
    </div>

    {{-- Top Albums --}}
    <x-ui.card title="Top albums" class="mb-6">
        @if ($topAlbums->isEmpty())
            <x-ui.empty-state title="No data yet" />
        @else
            <div class="media-cast media-cast--grid">
                @foreach ($topAlbums as $album)
                    <a href="{{ route('music.show', $album) }}" class="media-cast__member">
                        @if ($album->image_path)
                            <img src="{{ $album->image_path }}" alt="{{ $album->name }}" class="media-cast__photo">
                        @else
                            <div class="media-cast__photo media-cast__photo--empty"></div>
                        @endif
                        <div class="media-cast__name">{{ $album->name }}</div>
                        <div class="media-cast__role">{{ $album->artist->name }}</div>
                        <div class="media-cast__role">{{ $album->listen_count }}×</div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">

        {{-- Top Artists --}}
        <x-ui.card title="Top artists">
            @if ($topArtists->isEmpty())
                <x-ui.empty-state title="No data yet" />
            @else
                <div class="flex flex-col gap-3">
                    @foreach ($topArtists as $i => $artist)
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-[var(--color-text-muted)]" style="width:1.5rem;">{{ $i + 1 }}</span>
                            @if ($artist->image_path)
                                <img src="{{ $artist->image_path }}" alt="{{ $artist->name }}"
                                     style="width:2rem;height:2rem;border-radius:50%;object-fit:cover;flex-shrink:0;">
                            @else
                                <div style="width:2rem;height:2rem;border-radius:50%;background:var(--color-bg-tertiary);flex-shrink:0;"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('music.artists.show', $artist) }}" class="text-sm font-medium hover:underline truncate block">
                                    {{ $artist->name }}
                                </a>
                            </div>
                            <span class="text-sm text-[var(--color-text-muted)]">{{ $artist->total_listens }}×</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Rating distribution --}}
        <x-ui.card title="Rating distribution">
            @if (array_sum($ratingDistribution) === 0)
                <x-ui.empty-state title="No ratings yet" />
            @else
                @php $maxCount = max($ratingDistribution) ?: 1; @endphp
                <div class="flex flex-col gap-2">
                    @foreach (array_reverse(array_keys($ratingDistribution), true) as $rating)
                        @php $count = $ratingDistribution[$rating]; @endphp
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-[var(--color-text-muted)]" style="width:2rem;text-align:right;">{{ $rating }}</span>
                            <div style="flex:1;height:0.5rem;background:var(--color-bg-tertiary);border-radius:999px;overflow:hidden;">
                                <div style="width:{{ round(($count / $maxCount) * 100) }}%;height:100%;background:var(--color-brand);border-radius:999px;"></div>
                            </div>
                            <span class="text-sm text-[var(--color-text-muted)]" style="width:2rem;">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>

    {{-- First & Last listen --}}
    @if ($firstListen || $lastListen)
        <div class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-2">
            @if ($firstListen)
                <x-ui.card title="First listen">
                    <div class="flex items-center gap-4">
                        @if ($firstListen->image_path)
                            <img src="{{ $firstListen->image_path }}" alt="{{ $firstListen->name }}"
                                 style="width:3rem;height:3rem;object-fit:cover;border-radius:var(--radius-sm);flex-shrink:0;">
                        @endif
                        <div>
                            <a href="{{ route('music.show', $firstListen) }}" class="font-medium hover:underline">
                                {{ $firstListen->name }}
                            </a>
                            <div class="text-sm text-[var(--color-text-muted)] mt-1">
                                {{ $firstListen->artist->name }} · {{ $firstListen->first_listened_at?->format('d M Y') }}
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @endif
            @if ($lastListen)
                <x-ui.card title="Most recent">
                    <div class="flex items-center gap-4">
                        @if ($lastListen->image_path)
                            <img src="{{ $lastListen->image_path }}" alt="{{ $lastListen->name }}"
                                 style="width:3rem;height:3rem;object-fit:cover;border-radius:var(--radius-sm);flex-shrink:0;">
                        @endif
                        <div>
                            <a href="{{ route('music.show', $lastListen) }}" class="font-medium hover:underline">
                                {{ $lastListen->name }}
                            </a>
                            <div class="text-sm text-[var(--color-text-muted)] mt-1">
                                {{ $lastListen->artist->name }} · {{ $lastListen->last_listened_at?->format('d M Y') }}
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @endif
        </div>
    @endif

    {{-- Recent history --}}
    <x-ui.card title="Recent listen history">
        @if (empty($recentHistory))
            <x-ui.empty-state title="No listens yet" />
        @else
            <div class="flex flex-col gap-3">
                @foreach ($recentHistory as $day)
                    <div>
                        <div class="text-xs text-[var(--color-text-muted)] mb-1">{{ $day['date'] }}</div>
                        <div class="text-sm text-[var(--color-text-primary)]">{{ $day['titles'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

</x-layouts.app>
