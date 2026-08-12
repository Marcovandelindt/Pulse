<x-layouts.app title="TV Series">

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
            const res = await fetch('{{ route('tv.search') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ query: this.searchQuery }),
            });
            const data = await res.json();
            this.searchResults = data.results ?? [];
            this.searching = false;
        },

        async addSeries(tmdbId) {
            this.adding = tmdbId;
            const res = await fetch('{{ route('tv.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ tmdb_id: tmdbId }),
            });
            const data = await res.json();
            this.adding = null;
            this.searchResults = this.searchResults.map(r => r.tmdb_id === tmdbId ? { ...r, already_added: true } : r);
            this.$dispatch('toast', { message: `${data.name} added!`, type: 'success' });
        },

        matchesFilter(el) {
            if (!this.filter) return true;
            const q = this.filter.toLowerCase();
            return (el.dataset.name ?? '').toLowerCase().includes(q)
                || (el.dataset.original ?? '').toLowerCase().includes(q)
                || (el.dataset.nameEn ?? '').toLowerCase().includes(q);
        },
    }"
    @keydown.escape.window="addOpen = false; searchResults = []"
>

    <x-layout.page-header title="TV Series">
        <x-slot:actions>
            <a href="{{ route('tv.stats') }}" class="btn btn--secondary btn--sm">Statistics</a>
            <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Series</button>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($series->isNotEmpty())
        <div class="mb-4">
            <input
                type="text"
                x-model="filter"
                class="form-input"
                placeholder="Filter series…"
                style="max-width: 24rem;"
            >
        </div>
    @endif

    @if ($series->isEmpty())
        <x-ui.empty-state title="No TV series yet" description="Add your first series to get started.">
            <x-slot:action>
                <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Series</button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="media-grid">
            @foreach ($series as $show)
                <div
                    class="media-card"
                    data-name="{{ strtolower($show->name) }}"
                    data-original="{{ strtolower($show->original_name) }}"
                    data-name-en="{{ strtolower($show->name_en ?? '') }}"
                    x-show="matchesFilter($el)"
                >
                    <a href="{{ route('tv.show', $show) }}" class="media-card__poster-link">
                        @if ($show->poster_url)
                            <img src="{{ $show->poster_url }}" alt="{{ $show->name }}" class="media-card__poster">
                        @else
                            <div class="media-card__poster media-card__poster--empty">
                                <svg viewBox="0 0 24 24" fill="currentColor" style="width:2rem;height:2rem;opacity:0.3">
                                    <path d="M2 7a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V7zm2 0v10h16V7H4zm2 2h4v4H6V9zm6 0h6v2h-6V9zm0 4h4v2h-4v-2z"/>
                                </svg>
                            </div>
                        @endif
                        @if ($show->completion_percentage >= 100)
                            <span class="media-card__badge media-card__badge--success">✓</span>
                        @elseif ($show->episodes_watched > 0)
                            <span class="media-card__badge">{{ round($show->completion_percentage) }}%</span>
                        @endif
                    </a>
                    <div class="media-card__body">
                        <a href="{{ route('tv.show', $show) }}" class="media-card__title">
                            {{ $show->name_en ?? $show->name }}
                        </a>
                        <div class="media-card__meta">
                            {{ $show->first_air_date?->year ?? '—' }}
                            @if ($show->vote_average) · ★ {{ $show->vote_average }} @endif
                        </div>
                        @if ($show->episodes_watched > 0)
                            <div class="media-card__progress">
                                <div class="media-card__progress-bar"
                                     style="width: {{ min(100, $show->completion_percentage) }}%"></div>
                            </div>
                            <div class="media-card__watched">{{ $show->episodes_watched }} / {{ $show->number_of_episodes }} ep</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Series modal --}}
    <div class="modal" x-show="addOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="addOpen = false; searchResults = []"></div>
        <div class="modal__panel modal__panel--wide">
            <div class="modal__header">
                <h2 class="modal__title">Add TV Series</h2>
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
                <template x-for="show in searchResults" :key="show.tmdb_id">
                    <div class="media-search__item">
                        <img :src="show.poster_url ?? ''" :alt="show.name"
                             class="media-search__poster" x-show="show.poster_url">
                        <div class="media-search__info">
                            <div class="media-search__title" x-text="show.name"></div>
                            <div class="media-search__meta" x-text="show.year ?? '—'"></div>
                            <div class="media-search__overview" x-text="show.overview"></div>
                        </div>
                        <div class="media-search__actions">
                            <template x-if="show.already_added">
                                <span class="badge badge--success">Added</span>
                            </template>
                            <template x-if="!show.already_added">
                                <button class="btn btn--primary btn--sm"
                                        :disabled="adding === show.tmdb_id"
                                        @click="addSeries(show.tmdb_id)"
                                        x-text="adding === show.tmdb_id ? 'Adding…' : 'Add'">
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
