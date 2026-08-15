<x-layouts.app title="Recommendations">

<div
    x-data="recommendations({
        mode: '{{ $mode }}',
        actorBaseUrl: '{{ url('/recommendations/actor') }}',
        searchUrl: '{{ route('recommendations.people.search') }}',
    })"
    @click.away="showDropdown = false"
>

    <x-layout.page-header title="Recommendations">
        <x-slot:actions>
            <div class="rec-toggle">
                <a href="{{ route('recommendations.index') }}"
                   class="rec-toggle__btn {{ $mode === 'general' ? 'rec-toggle__btn--active' : '' }}">
                    General
                </a>
                <button
                    class="rec-toggle__btn {{ $mode === 'actor' ? 'rec-toggle__btn--active' : '' }}"
                    @click="openActorSearch()"
                >
                    By actor
                </button>
            </div>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Actor search bar --}}
    <div x-show="showActorSearch" x-transition class="rec-actor-bar" style="display:none;">
        @if($mode === 'actor' && $person)
            <div class="rec-actor-current">
                <img
                    src="{{ $person->profile_url ?? asset('cast-placeholder.svg') }}"
                    alt="{{ $person->name }}"
                    class="rec-actor-current__photo"
                >
                <span class="rec-actor-current__name">{{ $person->name_en ?? $person->name }}</span>
                <span class="rec-actor-current__sep">·</span>
                <span class="rec-actor-current__hint">Search for another:</span>
            </div>
        @endif
        <div style="position: relative; display: inline-block;">
            <input
                type="text"
                x-model="actorSearch"
                x-ref="actorInput"
                @focus="if (actorSearch.length >= 2) showDropdown = searchResults.length > 0"
                @keydown.escape="showDropdown = false"
                class="form-input"
                placeholder="Search actor…"
                style="min-width: 22rem;"
            >
            <div class="rec-people-dropdown" x-show="showDropdown && searchResults.length > 0" style="display:none;">
                <template x-for="p in searchResults" :key="p.id">
                    <button class="rec-people-dropdown__item" @click="selectActor(p)">
                        <img
                            :src="p.profile_url ?? '{{ asset('cast-placeholder.svg') }}'"
                            :alt="p.name_en ?? p.name"
                            class="rec-people-dropdown__photo"
                        >
                        <span x-text="p.name_en ?? p.name"></span>
                        <span class="rec-people-dropdown__alt" x-show="p.name_en" x-text="p.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- ── GENERAL MODE ── --}}
    @if($mode === 'general')

        <div class="rec-filter-row">
            <div class="rec-filter-tabs">
                <button class="rec-filter-tab" :class="{ 'rec-filter-tab--active': showMovies }" @click="toggleMovies()">Movies</button>
                <button class="rec-filter-tab" :class="{ 'rec-filter-tab--active': showSeries }" @click="toggleSeries()">Series</button>
            </div>
        </div>

        @if($recommendations->isEmpty())
            <x-ui.empty-state
                title="No recommendations yet"
                description="Add more movies and series to get personalised recommendations."
            />
        @else
            <div class="media-grid">
                @foreach($recommendations as $rec)
                    <div
                        class="media-card"
                        x-show="{{ $rec['media_type'] === 'movie' ? 'showMovies' : 'showSeries' }}"
                    >
                        <div class="media-card__poster-link" style="position:relative; display:block;">
                            @if($rec['poster_url'])
                                <img src="{{ $rec['poster_url'] }}" alt="{{ $rec['title'] }}" class="media-card__poster">
                            @else
                                <div class="media-card__poster media-card__poster--empty"></div>
                            @endif
                            <span class="media-card__badge">{{ $rec['media_type'] === 'movie' ? 'Movie' : 'Series' }}</span>
                        </div>
                        <div class="media-card__body">
                            <div class="media-card__title">{{ $rec['title'] }}</div>
                            <div class="media-card__meta">
                                {{ $rec['year'] }}
                                @if($rec['vote_average'] > 0)
                                    · ★ {{ $rec['vote_average'] }}
                                @endif
                            </div>
                            <div class="rec-card__because">Similar to {{ $rec['because'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @endif

    {{-- ── ACTOR MODE ── --}}
    @if($mode === 'actor' && $person)

        <div class="rec-filter-row">
            <div class="rec-filter-tabs">
                <button class="rec-filter-tab" :class="{ 'rec-filter-tab--active': showUnwatched }" @click="toggleUnwatched()">Unwatched ({{ $credits->where('watched', false)->count() }})</button>
                <button class="rec-filter-tab" :class="{ 'rec-filter-tab--active': showWatched }"   @click="toggleWatched()">Watched ({{ $credits->where('watched', true)->count() }})</button>
            </div>
            <div class="rec-filter-tabs">
                <button class="rec-filter-tab" :class="{ 'rec-filter-tab--active': showMovies }" @click="toggleMovies()">Movies</button>
                <button class="rec-filter-tab" :class="{ 'rec-filter-tab--active': showSeries }" @click="toggleSeries()">Series</button>
            </div>
        </div>

        @if($credits->isEmpty())
            <x-ui.empty-state title="No credits found" description="TMDB returned no results for this person." />
        @else
            <div class="media-grid">
                @foreach($credits as $credit)
                    <div
                        class="media-card"
                        x-show="{{ $credit['watched'] ? 'showWatched' : 'showUnwatched' }} && {{ $credit['media_type'] === 'movie' ? 'showMovies' : 'showSeries' }}"
                    >
                        <div style="position:relative; display:block;">
                            @if($credit['poster_url'])
                                <img src="{{ $credit['poster_url'] }}" alt="{{ $credit['title'] }}" class="media-card__poster">
                            @else
                                <div class="media-card__poster media-card__poster--empty"></div>
                            @endif
                            <span class="media-card__badge">{{ $credit['media_type'] === 'movie' ? 'Movie' : 'Series' }}</span>
                            @if($credit['watched'])
                                <span class="rec-card__watched-badge">✓ Watched</span>
                            @endif
                        </div>
                        <div class="media-card__body">
                            <div class="media-card__title">{{ $credit['title'] }}</div>
                            <div class="media-card__meta">
                                {{ $credit['year'] }}
                                @if($credit['vote_average'] > 0)
                                    · ★ {{ $credit['vote_average'] }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @endif

</div>

</x-layouts.app>
