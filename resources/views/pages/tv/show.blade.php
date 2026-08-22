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
    x-data="tvShow({
        episodeWatches:   @js($episodeStates),
        seasonEpisodeIds: @js($seasonEpisodeIds),
        seriesId:   {{ $series->id }},
        seriesName: '{{ addslashes($series->name_en ?? $series->name) }}',
        userRating: {{ $series->user_rating ?? 'null' }},
        isFavorite: {{ $series->is_favorite ? 'true' : 'false' }},
        routes: {
            bulk:     '{{ route('tv.watches.bulk', $series) }}',
            refresh:  '{{ route('tv.refresh', $series) }}',
            destroy:  '{{ route('tv.destroy', $series) }}',
            index:    '{{ route('tv.index') }}',
            rating:   '{{ route('tv.rating', $series) }}',
            favorite: '{{ route('tv.favorite', $series) }}',
        },
    })"
    @keydown.escape.window="bulkOpen = false; watchOpen = false; seasonBulkOpen = false"
    @keydown.enter.window="if (watchOpen) addWatch(); else if (bulkOpen) bulkWatch(); else if (seasonBulkOpen) bulkWatchSeason()"
>

    {{-- Backdrop hero --}}
    @if ($series->backdrop_url)
        <div class="media-hero media-hero--centered" style="background-image: url('{{ $series->backdrop_url }}')">
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
                        <button
                            type="button"
                            @click="toggleFavorite()"
                            class="btn btn--secondary btn--sm"
                            :class="{ 'btn--favorite-active': isFavorite }"
                            x-text="isFavorite ? '★ Favorited' : '☆ Favorite'"
                        ></button>
                        <button type="button" @click="bulkOpen = true" class="btn btn--primary btn--sm">Mark all watched</button>
                        <button type="button" @click="refreshSeries()" class="btn btn--secondary btn--sm" :disabled="refreshing">
                            <span x-show="!refreshing">Refresh</span>
                            <span x-show="refreshing">Refreshing…</span>
                        </button>
                        <form method="POST" action="{{ route('tv.backdrop', $series) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="backdrop" id="tv-backdrop-input" accept="image/*" class="hidden" onchange="this.form.submit()">
                            <button type="button" onclick="document.getElementById('tv-backdrop-input').click()" class="btn btn--secondary btn--sm">🖼 Backdrop</button>
                        </form>
                        <button type="button" @click="removeSeries()" class="btn btn--danger btn--sm">Remove</button>
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
                        @if ($series->completion_percentage < 100)
                            <div class="media-detail__stat-value">{{ $series->episodes_watched }} / {{ $series->number_of_episodes }}</div>
                        @else
                            <div class="media-detail__stat-value">{{ $series->episodes_watched }}</div>
                        @endif
                        <div class="media-detail__stat-label">episodes watched</div>
                    </div>
                    <div class="media-detail__stat">
                        @php $watchedHours = $series->watched_runtime_minutes > 0 ? round($series->watched_runtime_minutes / 60, 1) : null; @endphp
                        <div class="media-detail__stat-value">{{ $watchedHours ? $watchedHours . 'h' : '—' }}</div>
                        <div class="media-detail__stat-label">time watched</div>
                    </div>
                    @if ($series->last_watched_at)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ $series->last_watched_at->format('d M Y') }}</div>
                            <div class="media-detail__stat-label">last watched</div>
                        </div>
                    @endif
                    @if ($series->vote_average)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">★ {{ $series->vote_average }}</div>
                            <div class="media-detail__stat-label">TMDB rating</div>
                        </div>
                    @endif
                    @if ($series->completion_percentage < 100)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ round($series->completion_percentage) }}%</div>
                            <div class="media-detail__stat-label">completion</div>
                        </div>
                    @else
                        <div class="media-detail__stat media-detail__stat--interactive" @click="ratingEditing = true">
                            <template x-if="!ratingEditing">
                                <div>
                                    <div class="media-detail__stat-value" x-text="userRating ? '★ ' + userRating : '—'"></div>
                                    <div class="media-detail__stat-label">your rating</div>
                                </div>
                            </template>
                            <template x-if="ratingEditing">
                                <div @click.stop>
                                    <input
                                        type="number"
                                        x-model="ratingInput"
                                        x-ref="ratingInput"
                                        x-effect="if (ratingEditing) $nextTick(() => $refs.ratingInput.focus())"
                                        @blur="saveRating()"
                                        @keydown.enter="saveRating()"
                                        @keydown.escape="ratingEditing = false; ratingInput = userRating ?? ''"
                                        class="form-input"
                                        min="1" max="10" step="0.1"
                                        placeholder="1–10"
                                        style="width:5rem; padding: 0.25rem 0.5rem; font-size:0.875rem;"
                                    >
                                    <div class="media-detail__stat-label">your rating</div>
                                </div>
                            </template>
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
                            <img
                                src="{{ $person->profile_url ?? asset('cast-placeholder.svg') }}"
                                alt="{{ $person->name }}"
                                class="media-cast__photo"
                            >
                            <div class="media-cast__name">{{ $person->name_en ?? $person->name }}</div>
                            @if($person->name_en)
                                <div class="media-cast__native-name">{{ $person->name }}</div>
                            @endif
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
                        <div class="media-season__header-wrap">
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
                            <button type="button"
                                    class="btn btn--secondary btn--sm media-season__bulk-btn"
                                    @click.stop="openBulkWatchSeason({{ $season->id }}, '{{ addslashes($season->name) }}')">
                                Mark all watched
                            </button>
                        </div>

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
                                             :class="isWatched({{ $episode->id }}) ? 'media-episode--watched' : ''"
                                             @click="openAddWatch({{ $episode->id }}, '{{ addslashes($episode->name) }}')">
                                            <div class="media-episode__number">
                                                E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
                                            </div>
                                            <div class="media-episode__info" style="flex:1">
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
                                                                @click.stop="deleteWatch(watch.id, {{ $episode->id }})"
                                                                title="Remove watch"
                                                            >&times;</button>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn--secondary btn--sm media-episode__up-to"
                                                @click.stop="openUpToWatch({{ $episode->id }}, '{{ addslashes($episode->name) }}')"
                                                title="Mark all episodes up to here as watched"
                                            >↑ Up to here</button>
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
                <h2 class="modal__title" x-text="watchUpTo ? 'Mark all up to here' : 'Mark as watched'"></h2>
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

    {{-- Season bulk mark watched modal --}}
    <div class="modal" x-show="seasonBulkOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="seasonBulkOpen = false"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title">Mark season as watched</h2>
                <button @click="seasonBulkOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>
            <p class="text-sm text-[var(--color-text-muted)] mb-4">
                This will mark all episodes of <strong x-text="pendingSeasonName"></strong> as watched once.
            </p>
            <div class="flex flex-col gap-4">
                <div class="form-group">
                    <label class="form-label">When did you watch?</label>
                    <div class="flex flex-col gap-2 mt-2">
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="seasonBulkDateMode" value="year"> Year only (default)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="seasonBulkDateMode" value="exact"> Exact date
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="seasonBulkDateMode" value="none"> No date
                        </label>
                    </div>
                </div>
                <div class="form-group" x-show="seasonBulkDateMode === 'year'">
                    <input type="number" x-model="seasonBulkYear" class="form-input"
                           min="1900" max="{{ today()->year }}" placeholder="{{ today()->year }}">
                </div>
                <div class="form-group" x-show="seasonBulkDateMode === 'exact'">
                    <input type="date" x-model="seasonBulkDate" class="form-input" max="{{ today()->format('Y-m-d') }}">
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" @click="seasonBulkOpen = false" class="btn btn--secondary">Cancel</button>
                <button type="button" @click="bulkWatchSeason()" class="btn btn--primary">Mark all watched</button>
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
