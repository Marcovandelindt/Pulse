<x-layouts.app title="Music">

<div
    x-data="musicIndex({
        searchUrl: '{{ route('music.search') }}',
        storeUrl:  '{{ route('music.store') }}',
    })"
    @keydown.escape.window="addOpen = false; searchResults = []"
>

    <x-layout.page-header title="Music">
        <x-slot:actions>
            <a href="{{ route('music.stats') }}" class="btn btn--secondary btn--sm">Statistics</a>
            <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Album</button>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($albums->isEmpty())
        <x-ui.empty-state title="No albums yet" description="Add your first album to get started.">
            <x-slot:action>
                <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Album</button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="media-toolbar">
            <input
                type="text"
                x-model="filter"
                class="form-input"
                placeholder="Filter albums…"
                style="max-width: 24rem;"
            >
        </div>

        <div class="music-grid" id="album-grid">
            @foreach ($albums as $album)
                <div
                    class="album-card"
                    data-title="{{ strtolower($album->name) }}"
                    data-artist="{{ strtolower($album->artist->name) }}"
                    x-show="matchesFilter($el)"
                >
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
                        <a href="{{ route('music.artists.show', $album->artist) }}" class="album-card__artist">{{ $album->artist->name }}</a>
                        <div class="album-card__meta">
                            {{ $album->release_year ?? '—' }}
                            @if ($album->album_type) · {{ ucfirst($album->album_type) }} @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Album modal --}}
    <div class="modal" x-show="addOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="addOpen = false; searchResults = []"></div>
        <div class="modal__panel modal__panel--wide">
            <div class="modal__header">
                <h2 class="modal__title">Add Album</h2>
                <button @click="addOpen = false; searchResults = []" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>
            <div class="media-search">
                <input
                    type="text"
                    class="form-input"
                    placeholder="Search Spotify…"
                    x-model="searchQuery"
                    @input.debounce.400ms="search()"
                    x-effect="if (addOpen) $nextTick(() => $el.focus())"
                >
                <div class="media-search__spinner" x-show="searching" x-cloak>Searching…</div>
            </div>
            <div class="media-search__results" x-show="searchResults.length > 0">
                <template x-for="album in searchResults" :key="album.spotify_id">
                    <div class="media-search__item">
                        <img :src="album.image_url ?? ''" :alt="album.name"
                             class="media-search__poster" x-show="album.image_url">
                        <div class="media-search__info">
                            <div class="media-search__title" x-text="album.name"></div>
                            <div class="media-search__meta" x-text="(album.artist ?? '—') + (album.year ? ' · ' + album.year : '')"></div>
                        </div>
                        <div class="media-search__actions">
                            <button
                                class="btn btn--primary btn--sm"
                                :disabled="adding === album.spotify_id"
                                @click="addAlbum(album.spotify_id)"
                                x-text="adding === album.spotify_id ? 'Adding…' : 'Add'"
                            ></button>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="!searching && searchQuery && searchResults.length === 0" class="text-sm text-[var(--color-text-muted)] mt-4 text-center">
                No results found.
            </div>
        </div>
    </div>

</div>

</x-layouts.app>
