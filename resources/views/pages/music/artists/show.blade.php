<x-layouts.app title="{{ $artist->name }}">

    <x-layout.page-header title="{{ $artist->name }}">
        <x-slot:actions>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Artist profile card --}}
    <x-ui.card class="mb-6">
        <div class="flex gap-6 flex-wrap items-start">
            {{-- Artist image --}}
            <div style="flex-shrink:0;">
                @if ($artist->image_path)
                    <img src="{{ $artist->image_path }}" alt="{{ $artist->name }}"
                         style="width:150px;height:150px;border-radius:50%;object-fit:cover;">
                @else
                    <div style="width:150px;height:150px;border-radius:50%;background:var(--color-bg-tertiary);display:flex;align-items:center;justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:3rem;height:3rem;opacity:0.2">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Stats grid --}}
            <div class="flex-1">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <x-stats.stat-card label="Albums" :value="$albums->count()" />
                    <x-stats.stat-card label="Total listens" :value="$totalListens" />
                    <x-stats.stat-card label="Avg rating" :value="$averageRating ? number_format((float) $averageRating, 1) . '/10' : '—'" />
                    @if ($firstListened)
                        <x-stats.stat-card label="First listened" :value="$firstListened->format('Y')" />
                    @endif
                </div>

                @if ($artist->genres && count($artist->genres) > 0)
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach ($artist->genres as $genre)
                            <span class="badge badge--muted">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($firstListened || $lastListened)
                    <div class="text-sm text-[var(--color-text-muted)] mt-4 flex gap-6">
                        @if ($firstListened)
                            <span>First: {{ $firstListened->format('d M Y') }}</span>
                        @endif
                        @if ($lastListened)
                            <span>Last: {{ $lastListened->format('d M Y') }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{-- Albums grid --}}
    <x-ui.card title="Albums">
        @if ($albums->isEmpty())
            <x-ui.empty-state title="No albums" />
        @else
            <div class="music-grid">
                @foreach ($albums as $album)
                    <div class="album-card">
                        <a href="{{ route('music.show', $album) }}" class="album-card__cover-link">
                            @if ($album->image_path)
                                <img src="{{ $album->image_path }}" alt="{{ $album->name }}" class="album-card__cover">
                            @else
                                <div class="album-card__cover album-card__placeholder">
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:2rem;height:2rem;opacity:0.3">
                                        <path d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                </div>
                            @endif
                            @if ($album->listen_count > 0)
                                <span class="media-card__badge">{{ $album->listen_count }}×</span>
                            @endif
                        </a>
                        <div class="album-card__body">
                            <a href="{{ route('music.show', $album) }}" class="album-card__title">{{ $album->name }}</a>
                            <div class="album-card__meta">
                                {{ $album->release_year ?? '—' }}
                                @if ($album->album_type) · {{ ucfirst($album->album_type) }} @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

</x-layouts.app>
