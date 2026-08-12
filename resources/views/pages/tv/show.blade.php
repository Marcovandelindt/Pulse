<x-layouts.app title="{{ $series->name }}">

@php
$episodeStates   = [];
$seasonEpisodeIds = [];
foreach ($series->seasons as $season) {
    $ids = [];
    foreach ($season->episodes as $ep) {
        $episodeStates[$ep->id] = $ep->watches->map(fn ($w) => [
            'id'   => $w->id,
            'date' => $w->formattedWatchedAt(),
        ])->values()->toArray();
        $ids[] = $ep->id;
    }
    $seasonEpisodeIds[$season->id] = $ids;
}
@endphp

<div
    x-data="{
        episodeWatches: @js($episodeStates),
        seasonEpisodeIds: @js($seasonEpisodeIds),
        openSeasons: {},
        processing: {},
        showAllCast: false,

        watchOpen: false,
        pendingEpisodeId: null,
        pendingEpisodeName: '',
        watchDateMode: 'exact',
        watchDate: '{{ today()->format('Y-m-d') }}',
        watchYear: '{{ today()->year }}',

        bulkOpen: false,
        bulkDateMode: 'year',
        bulkDate: '{{ today()->format('Y-m-d') }}',
        bulkYear: '{{ today()->year }}',

        init() {
            const stored = localStorage.getItem('openSeasons_{{ $series->id }}');
            if (stored) {
                try { this.openSeasons = JSON.parse(stored); } catch(e) {}
            }
        },

        toggleSeason(id) {
            this.openSeasons[id] = !this.openSeasons[id];
            localStorage.setItem('openSeasons_{{ $series->id }}', JSON.stringify(this.openSeasons));
        },
        isSeasonOpen(id) { return !!this.openSeasons[id]; },

        isWatched(id) { return (this.episodeWatches[id] ?? []).length > 0; },
        watchesForEpisode(id) { return this.episodeWatches[id] ?? []; },

        watchedInSeason(seasonId) {
            return (this.seasonEpisodeIds[seasonId] ?? []).filter(id => this.isWatched(id)).length;
        },

        openAddWatch(episodeId, episodeName) {
            this.pendingEpisodeId   = episodeId;
            this.pendingEpisodeName = episodeName;
            this.watchOpen = true;
        },

        async addWatch() {
            const episodeId = this.pendingEpisodeId;
            if (!episodeId) return;
            this.watchOpen = false;
            this.processing[episodeId] = true;

            let watchedAt = null;
            let yearOnly  = false;
            if (this.watchDateMode === 'exact') {
                watchedAt = this.watchDate;
            } else if (this.watchDateMode === 'year') {
                watchedAt = this.watchYear + '-01-01';
                yearOnly  = true;
            }

            try {
                const res = await fetch(`/tv/episodes/${episodeId}/watches`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ watched_at: watchedAt, year_only: yearOnly }),
                });
                const data = await res.json();
                if (!this.episodeWatches[episodeId]) this.episodeWatches[episodeId] = [];
                this.episodeWatches[episodeId].push({ id: data.watch_id, date: data.formatted_date });
                this.$dispatch('toast', { message: 'Watch saved!', type: 'success' });
            } finally {
                this.processing[episodeId] = false;
                this.pendingEpisodeId = null;
            }
        },

        async deleteWatch(watchId, episodeId) {
            await fetch(`/tv/watches/${watchId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            this.episodeWatches[episodeId] = (this.episodeWatches[episodeId] ?? []).filter(w => w.id !== watchId);
            this.$dispatch('toast', { message: 'Watch removed', type: 'success' });
        },

        async bulkWatch() {
            if (!confirm('Mark ALL episodes of {{ addslashes($series->name) }} as watched?')) return;
            this.bulkOpen = false;

            let watchedAt = null;
            let yearOnly  = false;
            if (this.bulkDateMode === 'exact') {
                watchedAt = this.bulkDate;
            } else if (this.bulkDateMode === 'year') {
                watchedAt = this.bulkYear + '-01-01';
                yearOnly  = true;
            }

            const res = await fetch('{{ route('tv.watches.bulk', $series) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ watched_at: watchedAt, year_only: yearOnly }),
            });
            const data = await res.json();
            this.$dispatch('toast', { message: `Marked ${data.episodes_watched} episodes as watched!`, type: 'success' });
            setTimeout(() => window.location.reload(), 1500);
        },

        refreshing: false,
        async refreshSeries() {
            this.refreshing = true;
            this.$dispatch('toast', { message: 'Refreshing from TMDB…', type: 'success' });
            await fetch('{{ route('tv.refresh', $series) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            this.$dispatch('toast', { message: 'Series refreshed! Reloading…', type: 'success' });
            setTimeout(() => window.location.reload(), 1200);
        },

        async removeSeries() {
            if (!confirm('Remove this series and all watch history?')) return;
            await fetch('{{ route('tv.destroy', $series) }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            window.location.href = '{{ route('tv.index') }}';
        },
    }"
    @keydown.escape.window="bulkOpen = false; watchOpen = false"
    @keydown.enter.window="if (watchOpen) addWatch(); else if (bulkOpen) bulkWatch()"
>

    {{-- Backdrop hero --}}
    @if ($series->backdrop_url)
        <div class="media-hero" style="background-image: url('{{ $series->backdrop_url }}')">
            <div class="media-hero__overlay"></div>
        </div>
    @endif

    <div class="media-detail">

        {{-- Poster + info --}}
        <div class="media-detail__main">
            <div class="media-detail__poster-wrap">
                @if ($series->poster_url)
                    <img src="{{ $series->poster_url }}" alt="{{ $series->name }}" class="media-detail__poster">
                @endif
            </div>

            <div class="media-detail__info">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="media-detail__title">{{ $series->name_en ?? $series->name }}</h1>
                        @if ($series->original_name && $series->original_name !== $series->name)
                            <div class="media-detail__original-title">{{ $series->original_name }}</div>
                        @endif
                        @if ($series->name_en && $series->name_en !== $series->name)
                            <div class="media-detail__original-title">{{ $series->name }}</div>
                        @endif
                    </div>
                    <div class="flex gap-2 flex-wrap justify-end shrink-0">
                        <button @click="bulkOpen = true" class="btn btn--primary btn--sm">Mark all watched</button>
                        <button @click="refreshSeries()" class="btn btn--secondary btn--sm" :disabled="refreshing">
                            <span x-show="!refreshing">Refresh</span>
                            <span x-show="refreshing">Refreshing…</span>
                        </button>
                        <button @click="removeSeries()" class="btn btn--danger btn--sm">Remove</button>
                        <a href="{{ route('tv.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
                    </div>
                </div>

                <div class="media-detail__meta-row">
                    @if ($series->first_air_date) <span>{{ $series->first_air_date->year }}</span> @endif
                    @if ($series->status) <span>{{ $series->status }}</span> @endif
                    @if ($series->original_language) <span>{{ strtoupper($series->original_language) }}</span> @endif
                    @if ($series->vote_average) <span>★ {{ $series->vote_average }}</span> @endif
                    @if ($series->number_of_seasons) <span>{{ $series->number_of_seasons }} seasons</span> @endif
                </div>

                @if ($series->genres)
                    <div class="media-detail__genres">
                        @foreach ($series->genres as $genre)
                            <span class="badge badge--muted">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($series->overview)
                    <p class="media-detail__overview">{{ $series->overview }}</p>
                @endif

                <div class="media-detail__stats-row">
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value">{{ $series->episodes_watched }}</div>
                        <div class="media-detail__stat-label">episodes watched</div>
                    </div>
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value">{{ round($series->completion_percentage) }}%</div>
                        <div class="media-detail__stat-label">completion</div>
                    </div>
                    @if ($series->last_watched_at)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ $series->last_watched_at->format('d M Y') }}</div>
                            <div class="media-detail__stat-label">last watched</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Cast --}}
        @if ($series->people->isNotEmpty())
            <x-ui.card title="Cast" class="mt-6">
                <div class="media-cast media-cast--grid">
                    @foreach ($series->people->take(40) as $person)
                        <a
                            href="{{ route('people.show', $person) }}"
                            class="media-cast__member"
                            x-show="{{ $loop->index }} < 20 || showAllCast"
                        >
                            @if ($person->profile_url)
                                <img src="{{ $person->profile_url }}" alt="{{ $person->name }}" class="media-cast__photo">
                            @else
                                <div class="media-cast__photo media-cast__photo--empty"></div>
                            @endif
                            <div class="media-cast__name">{{ $person->name }}</div>
                            @if ($person->pivot->character)
                                <div class="media-cast__role">{{ $person->pivot->character }}</div>
                            @endif
                            @if ($person->pivot->episode_count)
                                <div class="media-cast__episodes">{{ $person->pivot->episode_count }} ep</div>
                            @endif
                        </a>
                    @endforeach
                </div>
                @if ($series->people->count() > 20)
                    <button
                        @click="showAllCast = !showAllCast"
                        class="btn btn--secondary btn--sm mt-4"
                        x-text="showAllCast ? 'Show less' : 'Show {{ min($series->people->count(), 40) - 20 }} more'"
                    ></button>
                @endif
            </x-ui.card>
        @endif

        {{-- Seasons accordion --}}
        @if ($series->seasons->isNotEmpty())
            <div class="mt-6">
                @foreach ($series->seasons as $season)
                    @if ($season->season_number === 0) @continue @endif
                    <div class="media-season">
                        <button type="button"
                                class="media-season__header"
                                @click="toggleSeason({{ $season->id }})">
                            <svg x-bind:style="isSeasonOpen({{ $season->id }}) ? 'transform:rotate(90deg)' : ''"
                                 class="media-season__arrow"
                                 viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                      d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                      clip-rule="evenodd" />
                            </svg>
                            <span class="media-season__name">{{ $season->name }}</span>
                            @if ($season->air_date)
                                <span class="media-season__meta">{{ $season->air_date->year }}</span>
                            @endif
                            <span class="media-season__progress"
                                  x-text="`${watchedInSeason({{ $season->id }})} / {{ $season->episodes->count() }} watched`">
                            </span>
                        </button>

                        <div class="media-season__body"
                             x-show="isSeasonOpen({{ $season->id }})"
                             x-transition
                             style="display:none;">
                            @if ($season->episodes->isEmpty())
                                <div class="media-season__empty">No episodes available.</div>
                            @else
                                <div class="media-episodes">
                                    @foreach ($season->episodes as $episode)
                                        <div class="media-episode"
                                             :class="isWatched({{ $episode->id }}) ? 'media-episode--watched' : ''">
                                            <div class="media-episode__number">
                                                E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
                                            </div>
                                            <div class="media-episode__info">
                                                <div class="media-episode__name">{{ $episode->name }}</div>
                                                <div class="media-episode__meta">
                                                    @if ($episode->air_date)
                                                        <span>{{ $episode->air_date->format('d M Y') }}</span>
                                                    @endif
                                                    @if ($episode->runtime)
                                                        <span>{{ $episode->runtime }} min</span>
                                                    @endif
                                                </div>
                                                <div class="media-episode__pills" x-show="watchesForEpisode({{ $episode->id }}).length > 0" style="display:none;">
                                                    <template x-for="watch in watchesForEpisode({{ $episode->id }})" :key="watch.id">
                                                        <span class="ep-watch-badge">
                                                            <span class="ep-watch-badge__date" x-text="watch.date || '?'"></span>
                                                            <button
                                                                class="ep-watch-badge__delete"
                                                                @click="deleteWatch(watch.id, {{ $episode->id }})"
                                                                title="Remove watch"
                                                            >&times;</button>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="media-episode__action">
                                                <button
                                                    type="button"
                                                    class="btn btn--secondary btn--sm"
                                                    :disabled="processing[{{ $episode->id }}]"
                                                    @click="openAddWatch({{ $episode->id }}, '{{ addslashes($episode->name) }}')"
                                                >+ Watch</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- Add Watch modal --}}
    <div class="modal" x-show="watchOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="watchOpen = false; pendingEpisodeId = null"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title">Mark as watched</h2>
                <button @click="watchOpen = false; pendingEpisodeId = null" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>
            <div class="text-sm text-[var(--color-text-muted)] mb-4" x-text="pendingEpisodeName" x-show="pendingEpisodeName"></div>
            <div class="flex flex-col gap-4">
                <div class="form-group">
                    <label class="form-label">When did you watch?</label>
                    <div class="flex flex-col gap-2 mt-2">
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="watchDateMode" value="exact"> Exact date
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="watchDateMode" value="year"> Year only
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="watchDateMode" value="none"> No date
                        </label>
                    </div>
                </div>
                <div class="form-group" x-show="watchDateMode === 'exact'">
                    <input type="date" x-model="watchDate" class="form-input"
                           max="{{ today()->format('Y-m-d') }}"
                           x-effect="if (watchOpen && watchDateMode === 'exact') $nextTick(() => $el.focus())">
                </div>
                <div class="form-group" x-show="watchDateMode === 'year'">
                    <input type="number" x-model="watchYear" class="form-input"
                           min="1900" max="{{ today()->year }}" placeholder="{{ today()->year }}">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" @click="watchOpen = false; pendingEpisodeId = null" class="btn btn--secondary">Cancel</button>
                <button type="button" @click="addWatch()" class="btn btn--primary">Save</button>
            </div>
        </div>
    </div>

    {{-- Bulk mark watched modal --}}
    <div class="modal" x-show="bulkOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="bulkOpen = false"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title">Mark all episodes as watched</h2>
                <button @click="bulkOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>
            <p class="text-sm text-[var(--color-text-muted)] mb-4">
                This will mark all episodes of <strong>{{ $series->name_en ?? $series->name }}</strong> as watched once.
            </p>
            <div class="flex flex-col gap-4">
                <div class="form-group">
                    <label class="form-label">When did you watch?</label>
                    <div class="flex flex-col gap-2 mt-2">
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="bulkDateMode" value="year"> Year only (default)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="bulkDateMode" value="exact"> Exact date
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="bulkDateMode" value="none"> No date
                        </label>
                    </div>
                </div>
                <div class="form-group" x-show="bulkDateMode === 'year'">
                    <input type="number" x-model="bulkYear" class="form-input"
                           min="1900" max="{{ today()->year }}" placeholder="{{ today()->year }}">
                </div>
                <div class="form-group" x-show="bulkDateMode === 'exact'">
                    <input type="date" x-model="bulkDate" class="form-input" max="{{ today()->format('Y-m-d') }}">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" @click="bulkOpen = false" class="btn btn--secondary">Cancel</button>
                <button type="button" @click="bulkWatch()" class="btn btn--primary">Mark all watched</button>
            </div>
        </div>
    </div>

</div>

</x-layouts.app>
