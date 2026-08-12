<x-layouts.app title="Movies">

<div
    x-data="{
        filter: '',
        addOpen: false,
        searchQuery: '',
        searchResults: [],
        searching: false,
        adding: null,

        async search() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            this.searching = true;
            const res = await fetch('{{ route('movies.search') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ query: this.searchQuery }),
            });
            const data = await res.json();
            this.searchResults = data.results ?? [];
            this.searching = false;
        },

        async addMovie(tmdbId) {
            this.adding = tmdbId;
            const res = await fetch('{{ route('movies.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ tmdb_id: tmdbId }),
            });
            const data = await res.json();
            this.adding = null;
            this.searchResults = this.searchResults.map(r => r.tmdb_id === tmdbId ? { ...r, already_added: true } : r);
            this.$dispatch('toast', { message: `${data.title} added!`, type: 'success' });
            setTimeout(() => window.location.reload(), 1000);
        },

        matchesFilter(el) {
            if (!this.filter) return true;
            const q = this.filter.toLowerCase();
            return (el.dataset.title ?? '').toLowerCase().includes(q)
                || (el.dataset.originalTitle ?? '').toLowerCase().includes(q);
        },
    }"
    @keydown.escape.window="addOpen = false; searchResults = []"
>

    <x-layout.page-header title="Movies">
        <x-slot:actions>
            <a href="{{ route('movies.stats') }}" class="btn btn--secondary btn--sm">Statistics</a>
            <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Movie</button>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($movies->isNotEmpty())
        <div class="mb-4">
            <input
                type="text"
                x-model="filter"
                class="form-input"
                placeholder="Filter movies…"
                style="max-width: 24rem;"
            >
        </div>
    @endif

    @if ($movies->isEmpty())
        <x-ui.empty-state title="No movies yet" description="Add your first movie to get started.">
            <x-slot:action>
                <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Movie</button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="media-grid" id="movie-grid">
            @foreach ($movies as $movie)
                <div
                    class="media-card"
                    data-title="{{ strtolower($movie->title) }}"
                    data-original-title="{{ strtolower($movie->original_title) }}"
                    x-show="matchesFilter($el)"
                >
                    <a href="{{ route('movies.show', $movie) }}" class="media-card__poster-link">
                        @if ($movie->poster_url)
                            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="media-card__poster">
                        @else
                            <div class="media-card__poster media-card__poster--empty">
                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:2rem;height:2rem;opacity:0.3">
                                    <path d="M4 4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 3h12v8H6V7zm2 2v4l3-2-3-2z"/>
                                </svg>
                            </div>
                        @endif
                        @if ($movie->watch_count > 0)
                            <span class="media-card__badge">{{ $movie->watch_count }}×</span>
                        @endif
                    </a>
                    <div class="media-card__body">
                        <a href="{{ route('movies.show', $movie) }}" class="media-card__title">{{ $movie->title }}</a>
                        <div class="media-card__meta">
                            {{ $movie->release_date?->year ?? '—' }}
                            @if ($movie->runtime)
                                · {{ intdiv($movie->runtime, 60) }}h {{ $movie->runtime % 60 }}m
                            @endif
                            @if ($movie->vote_average)
                                · ★ {{ $movie->vote_average }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Movie modal --}}
    <div class="modal" x-show="addOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="addOpen = false; searchResults = []"></div>
        <div class="modal__panel modal__panel--wide">
            <div class="modal__header">
                <h2 class="modal__title">Add Movie</h2>
                <button @click="addOpen = false; searchResults = []" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>
            <div class="media-search">
                <input
                    type="text"
                    class="form-input"
                    placeholder="Search TMDB…"
                    x-model="searchQuery"
                    @input.debounce.400ms="search()"
                    x-effect="if (addOpen) $nextTick(() => $el.focus())"
                >
                <div class="media-search__spinner" x-show="searching" x-cloak>Searching…</div>
            </div>
            <div class="media-search__results" x-show="searchResults.length > 0">
                <template x-for="movie in searchResults" :key="movie.tmdb_id">
                    <div class="media-search__item">
                        <img :src="movie.poster_url ?? ''" :alt="movie.title"
                             class="media-search__poster" x-show="movie.poster_url">
                        <div class="media-search__info">
                            <div class="media-search__title" x-text="movie.title"></div>
                            <div class="media-search__meta" x-text="movie.year ?? '—'"></div>
                            <div class="media-search__overview" x-text="movie.overview"></div>
                        </div>
                        <div class="media-search__actions">
                            <template x-if="movie.already_added">
                                <span class="badge badge--success">Added</span>
                            </template>
                            <template x-if="!movie.already_added">
                                <button class="btn btn--primary btn--sm"
                                        :disabled="adding === movie.tmdb_id"
                                        @click="addMovie(movie.tmdb_id)"
                                        x-text="adding === movie.tmdb_id ? 'Adding…' : 'Add'">
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>

</x-layouts.app>
