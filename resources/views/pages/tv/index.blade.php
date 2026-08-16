<x-layouts.app title="TV Series">

@php
    $backdrops = $series
        ->filter(fn ($s) => $s->backdrop_url)
        ->map(fn ($s) => [
            'name'     => $s->name_en ?? $s->name,
            'url'      => $s->backdrop_url,
            'favorite' => (bool) $s->is_favorite,
            'stats'    => collect([
                $s->episodes_watched > 0 ? $s->episodes_watched . ' episodes watched' : null,
                $s->completion_percentage > 0 ? round($s->completion_percentage) . '% complete' : null,
            ])->filter()->implode(' · '),
        ])
        ->values();

    $sleepItems = $series->filter(fn ($s) => $s->poster_url)->map(fn ($s) => [
        'title'             => $s->name_en ?? $s->name,
        'year'              => $s->first_air_date?->year,
        'poster_url'        => $s->poster_url,
        'backdrop_url'      => $s->backdrop_url,
        'episodes_watched'  => $s->episodes_watched ?? 0,
        'total_episodes'    => $s->number_of_episodes,
        'completion'        => (int) round(min(100, $s->completion_percentage ?? 0)),
        'runtime_str'       => $s->watched_runtime_minutes > 0
            ? intdiv($s->watched_runtime_minutes, 60).'h '.($s->watched_runtime_minutes % 60).'m'
            : null,
        'url'               => route('tv.show', $s),
    ])->values();
@endphp

<div
    x-data="tvIndex({
        searchUrl: '{{ route('tv.search') }}',
        storeUrl:  '{{ route('tv.store') }}',
        backdrops:  @js($backdrops),
        sleepItems: @js($sleepItems),
    })"
    @keydown.escape.window="addOpen = false; searchResults = []"
>

    <x-layout.page-header title="TV Series">
        <x-slot:actions>
            <button @click="enterSleep()" class="btn btn--secondary btn--sm" x-show="sleepItems.length > 0" style="display:none;">☾ Sleep</button>
            <a href="{{ route('tv.stats') }}" class="btn btn--secondary btn--sm">Statistics</a>
            <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add Series</button>
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
                <div class="media-slider__favorite" x-show="slide.favorite">★</div>
            </div>
        </template>
    </div>

    @if ($series->isNotEmpty())
        <div class="media-toolbar">
            <input
                type="text"
                x-model="filter"
                class="form-input"
                placeholder="Filter series…"
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
                    x-data="{ isFavorite: {{ $show->is_favorite ? 'true' : 'false' }} }"
                >
                    <div class="media-card__poster-wrap">
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
                        <button
                            class="media-card__favorite"
                            :class="{ 'media-card__favorite--active': isFavorite }"
                            @click.prevent="
                                isFavorite = !isFavorite;
                                fetch('{{ route('tv.favorite', $show) }}', {
                                    method: 'PATCH',
                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                });
                            "
                            :title="isFavorite ? 'Remove from favorites' : 'Add to favorites'"
                        >★</button>
                    </div>
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
                            @if ($show->watched_runtime_minutes > 0)
                                @php $h = intdiv($show->watched_runtime_minutes, 60); $m = $show->watched_runtime_minutes % 60; @endphp
                                <div class="media-card__runtime">{{ $h > 0 ? $h.'h ' : '' }}{{ $m > 0 ? $m.'m' : '' }} watched</div>
                            @endif
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

        <button class="sleep-mode__close" @click="exitSleep()">✕</button>

        <div class="sleep-mode__content" :class="{ 'sleep-mode__content--transitioning': sleepTransitioning }">
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
                        <div class="sleep-mode__stat-label">Episodes watched</div>
                        <div class="sleep-mode__stat"
                             x-text="`${sleepItem?.episodes_watched} / ${sleepItem?.total_episodes ?? '?'}`">
                        </div>
                        <div class="sleep-mode__bar-wrap">
                            <div class="sleep-mode__bar"
                                 :style="`width: ${sleepItem?.completion ?? 0}%`">
                            </div>
                        </div>
                    </div>
                    <template x-if="sleepItem?.runtime_str">
                        <div>
                            <div class="sleep-mode__stat-label">Time watched</div>
                            <div class="sleep-mode__stat" x-text="sleepItem?.runtime_str"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <button class="sleep-mode__nav sleep-mode__nav--prev" @click="_sleepPrev()">&#8249;</button>
        <button class="sleep-mode__nav sleep-mode__nav--next" @click="_sleepNext()">&#8250;</button>

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
