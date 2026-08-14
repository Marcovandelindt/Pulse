<x-layouts.app title="{{ $movie->title }}">

<div
    x-data="movieShow({
        watches: @js($watchesForAlpine),
        routes: {
            store:   '{{ route('movies.watches.store', $movie) }}',
            destroy: '{{ route('movies.destroy', $movie) }}',
            index:   '{{ route('movies.index') }}',
        },
    })"
    @keydown.escape.window="watchOpen = false"
    @keydown.enter.window="if (watchOpen) addWatch()"
>

    {{-- Backdrop hero --}}
    @if ($movie->backdrop_url)
        <div class="media-hero media-hero--centered" style="background-image: url('{{ $movie->backdrop_url }}')">
            <div class="media-hero__overlay"></div>
        </div>
    @endif

    <div class="media-detail">

        {{-- Poster + info --}}
        <div class="media-detail__main">
            <div class="media-detail__poster-wrap">
                @if ($movie->poster_url)
                    <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="media-detail__poster">
                @endif
            </div>

            <div class="media-detail__info">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="media-detail__title">{{ $movie->title }}</h1>
                        @if ($movie->original_title !== $movie->title)
                            <div class="media-detail__original-title">{{ $movie->original_title }}</div>
                        @endif
                    </div>
                    <div class="flex gap-2 flex-wrap justify-end shrink-0">
                        <button @click="watchOpen = true" class="btn btn--primary btn--sm">+ Mark watched</button>
                        <button @click="deleteMovie()" class="btn btn--danger btn--sm">Remove</button>
                        <a href="{{ route('movies.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
                    </div>
                </div>

                <div class="media-detail__meta-row">
                    @if ($movie->release_date) <span>{{ $movie->release_date->format('d M Y') }}</span> @endif
                    @if ($movie->runtime)
                        <span>{{ intdiv($movie->runtime, 60) }}h {{ $movie->runtime % 60 }}m</span>
                    @endif
                    @if ($movie->original_language) <span>{{ strtoupper($movie->original_language) }}</span> @endif
                    @if ($movie->vote_average) <span>★ {{ $movie->vote_average }}</span> @endif
                </div>

                @if ($movie->genres)
                    <div class="media-detail__genres">
                        @foreach ($movie->genres as $genre)
                            <span class="badge badge--muted">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($movie->overview)
                    <p class="media-detail__overview">{{ $movie->overview }}</p>
                @endif

                <div class="media-detail__stats-row">
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value" x-text="watches.length">{{ $movie->watch_count }}</div>
                        <div class="media-detail__stat-label">times watched</div>
                    </div>
                    @if ($totalRuntime > 0)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ round($totalRuntime / 60, 1) }}h</div>
                            <div class="media-detail__stat-label">total watch time</div>
                        </div>
                    @endif
                    @if ($averageRating)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ round((float) $averageRating, 1) }}</div>
                            <div class="media-detail__stat-label">avg rating</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Directors --}}
        @if ($directors->isNotEmpty())
            <div class="media-directors mt-6">
                @foreach ($directors as $director)
                    <a href="{{ route('people.show', $director) }}" class="media-director">
                        @if ($director->profile_url)
                            <img src="{{ $director->profile_url }}" alt="{{ $director->name }}" class="media-director__photo">
                        @else
                            <div class="media-director__photo media-director__photo--empty"></div>
                        @endif
                        <div>
                            <div class="media-director__name">{{ $director->name }}</div>
                            <div class="media-director__label">Director</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Cast --}}
        @if ($movie->people->isNotEmpty())
            <x-ui.card title="Cast" class="mt-6">
                <div class="media-cast media-cast--grid">
                    @foreach ($movie->people as $person)
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
                        </a>
                    @endforeach
                </div>
                @if ($movie->people->count() > 20)
                    <button
                        @click="showAllCast = !showAllCast"
                        class="btn btn--secondary btn--sm mt-4"
                        x-text="showAllCast ? 'Show less' : 'Show {{ $movie->people->count() - 20 }} more'"
                    ></button>
                @endif
            </x-ui.card>
        @endif

        {{-- Watch history --}}
        <x-ui.card title="Watch history" class="mt-6">
            <template x-if="watches.length === 0">
                <div class="text-sm text-[var(--color-text-muted)] py-2">Not watched yet.</div>
            </template>
            <template x-if="watches.length > 0">
                <div class="flex flex-wrap gap-2">
                    <template x-for="watch in watches" :key="watch.id">
                        <span class="ep-watch-badge ep-watch-badge--lg">
                            <span class="ep-watch-badge__date" x-text="watch.date || 'Date unknown'"></span>
                            <span class="ep-watch-badge__rating" x-show="watch.rating" x-text="'· ★' + watch.rating"></span>
                            <button
                                class="ep-watch-badge__delete"
                                @click="deleteWatch(watch.id)"
                                title="Delete watch"
                            >&times;</button>
                        </span>
                    </template>
                </div>
            </template>
        </x-ui.card>

    </div>

    {{-- Add Watch modal --}}
    <div class="modal" x-show="watchOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="watchOpen = false"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title">Mark as watched</h2>
                <button @click="watchOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>
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
                <div class="form-group">
                    <label class="form-label">Rating (1–10)</label>
                    <input type="number" x-model="watchRating" class="form-input" min="1" max="10" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea x-model="watchNotes" class="form-textarea" rows="3" placeholder="Optional…"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" @click="watchOpen = false" class="btn btn--secondary">Cancel</button>
                <button type="button" @click="addWatch()" class="btn btn--primary">Save</button>
            </div>
        </div>
    </div>

</div>

</x-layouts.app>
