<x-layouts.app title="Movies">

@php
    $backdrops = $movies
        ->filter(fn ($m) => $m->backdrop_url)
        ->map(fn ($m) => [
            'name' => $m->title,
            'url'  => $m->backdrop_url,
            'stats' => collect([
                $m->watch_count > 0 ? 'Watched ' . $m->watch_count . '×' : null,
                $m->runtime ? round($m->runtime / 60, 1) . 'h runtime' : null,
            ])->filter()->implode(' · '),
        ])
        ->values();

    $sleepItems = $movies->filter(fn ($m) => $m->poster_url)->map(fn ($m) => [
        'title'        => $m->title,
        'year'         => $m->release_date?->year,
        'poster_url'   => $m->poster_url,
        'backdrop_url' => $m->backdrop_url,
        'watch_count'  => $m->watch_count,
        'runtime_str'  => $m->runtime
            ? intdiv($m->runtime, 60).'h '.($m->runtime % 60).'m'
            : null,
        'last_watched' => $m->last_watched_at?->format('d M Y'),
        'url'          => route('movies.show', $m),
    ])->values();
@endphp

<div
    x-data="movieIndex({
        searchUrl: '{{ route('movies.search') }}',
        storeUrl:  '{{ route('movies.store') }}',
        backdrops:  @js($backdrops),
        sleepItems: @js($sleepItems),
    })"
    @keydown.escape.window="addOpen = false; searchResults = []"
>

    <x-layout.page-header title="Movies">
        <x-slot:actions>
            <button @click="enterSleep()" class="btn btn--secondary btn--sm" x-show="sleepItems.length > 0" style="display:none;">☾ Sleep</button>
            <a href="{{ route('movies.stats') }}" class="btn btn--secondary btn--sm">Statistics</a>
            <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Movie</button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="media-slider" x-show="backdrops.length > 0" style="display:none;">
        <template x-for="(slide, i) in backdrops" :key="i">
            <div
                class="media-slider__slide"
                :style="`background-image: url('${slide.url}')`"
                x-show="currentSlide === i"
                x-transition.opacity.duration.600ms
            >
                <div class="media-slider__overlay"></div>
                <div class="media-slider__info">
                    <div class="media-slider__label" x-text="slide.name"></div>
                    <div class="media-slider__stats" x-show="slide.stats" x-text="slide.stats"></div>
                </div>
            </div>
        </template>
    </div>

    @if ($movies->isNotEmpty())
        <div class="media-toolbar">
            <input
                type="text"
                x-model="filter"
                class="form-input"
                placeholder="Filter movies…"
                style="max-width: 24rem;"
            >
            <select
                class="form-input"
                style="max-width: 12rem;"
                onchange="window.location.href = '?sort=' + this.value"
            >
                <option value="most_watched" {{ $sort === 'most_watched' ? 'selected' : '' }}>Most watched</option>
                <option value="last_watched" {{ $sort === 'last_watched' ? 'selected' : '' }}>Last watched</option>
                <option value="added"        {{ $sort === 'added'        ? 'selected' : '' }}>Recently added</option>
                <option value="alpha"        {{ $sort === 'alpha'        ? 'selected' : '' }}>Alphabetical</option>
            </select>
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
                    x-show="!filter || $el.dataset.title.includes(filter.toLowerCase()) || ($el.dataset.originalTitle ?? '').includes(filter.toLowerCase())"
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
                        @if ($movie->last_watched_at)
                            <div class="media-card__watched-date">{{ $movie->last_watched_at->format('d M Y') }}</div>
                        @endif
                        @if ($movie->watch_count > 0 && $movie->runtime)
                            @php $totalMin = $movie->watch_count * $movie->runtime; $h = intdiv($totalMin, 60); $m = $totalMin % 60; @endphp
                            <div class="media-card__runtime">{{ $h > 0 ? $h.'h ' : '' }}{{ $m > 0 ? $m.'m' : '' }} watched</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Sleep mode --}}
    <div class="sleep-mode"
         x-show="sleepOpen"
         style="display:none;">
        <div class="sleep-mode__backdrop"
             :style="sleepItem ? `background-image: url('${sleepItem.backdrop_url || sleepItem.poster_url}')` : ''">
        </div>

        <div class="sleep-mode__fade" :class="{ 'sleep-mode__fade--active': sleepTransitioning }"></div>

        <button class="sleep-mode__close" @click="exitSleep()">✕</button>

        <div class="sleep-mode__clock">
            <div class="sleep-mode__clock-time" x-text="clockTime"></div>
            <div class="sleep-mode__clock-date" x-text="clockDate"></div>
        </div>

        <div class="sleep-mode__content">
            <div class="sleep-mode__poster-wrap">
                <img :src="sleepItem?.poster_url" :alt="sleepItem?.title" class="sleep-mode__poster">
            </div>
            <div class="sleep-mode__info">
                <div class="sleep-mode__title" x-text="sleepItem?.title"></div>
                <div class="sleep-mode__meta"
                     x-text="[sleepItem?.year, sleepItem?.runtime_str].filter(Boolean).join(' · ')">
                </div>
                <div class="sleep-mode__stats">
                    <div>
                        <div class="sleep-mode__stat-label">Times watched</div>
                        <div class="sleep-mode__stat"
                             x-text="sleepItem?.watch_count > 0 ? sleepItem.watch_count + '×' : 'Not yet watched'">
                        </div>
                    </div>
                    <template x-if="sleepItem?.last_watched">
                        <div>
                            <div class="sleep-mode__stat-label">Last watched</div>
                            <div class="sleep-mode__stat" x-text="sleepItem?.last_watched"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="sleep-mode__counter"
             x-text="`${sleepIndex + 1} / ${sleepItems.length}`">
        </div>

        <div class="sleep-progress">
            <div
                class="sleep-progress__fill"
                x-effect="sleepIndex; $el.style.animation = 'none'; $el.offsetWidth; $el.style.animation = ''"
            ></div>
        </div>
    </div>

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
