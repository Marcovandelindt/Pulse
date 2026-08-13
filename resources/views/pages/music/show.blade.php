<x-layouts.app title="{{ $album->name }}">

@php
    $listensForAlpine = $album->listens->map(fn ($l) => [
        'id'     => $l->id,
        'date'   => $l->formattedListenedAt(),
        'rating' => $l->rating,
        'notes'  => $l->notes,
    ])->values()->toArray();

    $discs = $album->tracks->groupBy('disc_number');
    $multiDisc = $discs->count() > 1;
@endphp

<div
    x-data="musicShow({
        listens: @js($listensForAlpine),
        routes: {
            store:         '{{ route('music.listens.store', $album) }}',
            destroyListen: '{{ url('music/listens/__ID__') }}',
            destroy:       '{{ route('music.destroy', $album) }}',
            index:         '{{ route('music.index') }}',
        },
    })"
    @keydown.escape.window="listenOpen = false"
    @keydown.enter.window="if (listenOpen) addListen()"
>

    <div class="media-detail">
        <div class="media-detail__main">

            {{-- Album art --}}
            <div class="media-detail__poster-wrap">
                @if ($album->image_path)
                    <img src="{{ $album->image_path }}" alt="{{ $album->name }}" class="media-detail__poster">
                @else
                    <div class="media-detail__poster" style="background:var(--color-bg-tertiary);display:flex;align-items:center;justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:4rem;height:4rem;opacity:0.2">
                            <path d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="media-detail__info">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="media-detail__title">{{ $album->name }}</h1>
                        <div class="media-detail__original-title">
                            <a href="{{ route('music.artists.show', $album->artist) }}" class="hover:underline">
                                {{ $album->artist->name }}
                            </a>
                        </div>
                    </div>
                    <div class="flex gap-2 flex-wrap justify-end shrink-0">
                        <button @click="listenOpen = true" class="btn btn--primary btn--sm">+ Log Listen</button>
                        <button @click="deleteAlbum()" class="btn btn--danger btn--sm">Remove</button>
                        <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
                    </div>
                </div>

                <div class="media-detail__meta-row">
                    @if ($album->release_date)
                        <span>{{ $album->release_date->format('d M Y') }}</span>
                    @elseif ($album->release_year)
                        <span>{{ $album->release_year }}</span>
                    @endif
                    @if ($album->album_type)
                        <span class="badge badge--muted">{{ ucfirst($album->album_type) }}</span>
                    @endif
                    @if ($album->track_count)
                        <span>{{ $album->track_count }} tracks</span>
                    @endif
                    @if ($album->formattedDuration)
                        <span>{{ $album->formattedDuration }}</span>
                    @endif
                </div>

                @if ($album->genres && count($album->genres) > 0)
                    <div class="media-detail__genres">
                        @foreach ($album->genres as $genre)
                            <span class="badge badge--muted">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($album->label)
                    <div class="text-sm text-[var(--color-text-muted)] mt-2">Label: {{ $album->label }}</div>
                @endif

                <div class="media-detail__stats-row">
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value" x-text="listens.length">{{ $album->listen_count }}</div>
                        <div class="media-detail__stat-label">listens</div>
                    </div>
                    @if ($averageRating)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ round((float) $averageRating, 1) }}/10</div>
                            <div class="media-detail__stat-label">avg rating</div>
                        </div>
                    @endif
                    @if ($album->last_listened_at)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ $album->last_listened_at->format('d M Y') }}</div>
                            <div class="media-detail__stat-label">last listened</div>
                        </div>
                    @endif
                </div>

                {{-- Featuring artists --}}
                @if ($album->artists->where('pivot.is_primary', false)->isNotEmpty())
                    <div class="text-sm text-[var(--color-text-muted)] mt-2">
                        feat.
                        @foreach ($album->artists->where('pivot.is_primary', false) as $feat)
                            <a href="{{ route('music.artists.show', $feat) }}" class="hover:underline">{{ $feat->name }}</a>@if (!$loop->last), @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Tracklist --}}
        @if ($album->tracks->isNotEmpty())
            <x-ui.card title="Tracklist" class="mt-6">
                <div class="tracklist">
                    @foreach ($discs as $discNumber => $tracks)
                        @if ($multiDisc)
                            <div class="tracklist__header">Disc {{ $discNumber }}</div>
                        @endif
                        @foreach ($tracks as $track)
                            <div class="tracklist__row">
                                <span class="tracklist__number">{{ $track->track_number }}</span>
                                <span class="tracklist__name">
                                    {{ $track->name }}
                                    @if ($track->is_explicit)
                                        <span class="tracklist__explicit">E</span>
                                    @endif
                                </span>
                                <span class="tracklist__duration">{{ $track->formattedDuration }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        {{-- Listen history --}}
        <x-ui.card title="Listen history" class="mt-6">
            <template x-if="listens.length === 0">
                <div class="text-sm text-[var(--color-text-muted)]">No listens logged yet.</div>
            </template>
            <template x-if="listens.length > 0">
                <div class="flex flex-col gap-2">
                    <template x-for="listen in listens" :key="listen.id">
                        <div class="flex items-center gap-4 py-2" style="border-bottom: 1px solid var(--color-border);">
                            <div class="flex-1">
                                <span class="text-sm text-[var(--color-text-primary)]" x-text="listen.date"></span>
                                <span x-show="listen.rating" class="text-sm text-[var(--color-text-muted)] ml-2" x-text="listen.rating + '/10'"></span>
                            </div>
                            <div x-show="listen.notes" class="text-sm text-[var(--color-text-muted)] flex-1 truncate" x-text="listen.notes"></div>
                            <button
                                @click="deleteListen(listen.id)"
                                :disabled="deleting === listen.id"
                                class="btn btn--danger btn--sm btn--icon"
                                title="Remove listen"
                            >&times;</button>
                        </div>
                    </template>
                </div>
            </template>
        </x-ui.card>

    </div>

    {{-- Log Listen modal --}}
    <div class="modal" x-show="listenOpen" x-transition style="display:none;">
        <div class="modal__backdrop" @click="listenOpen = false"></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h2 class="modal__title">Log Listen</h2>
                <button @click="listenOpen = false" class="btn btn--icon btn--secondary" type="button">&times;</button>
            </div>
            <div class="flex flex-col gap-4">
                <div class="form-group">
                    <label class="form-label">When did you listen?</label>
                    <div class="flex flex-col gap-2 mt-2">
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="listenDateMode" value="exact"> Exact date
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="listenDateMode" value="year"> Year only
                        </label>
                        <label class="flex items-center gap-2 text-sm text-[var(--color-text-muted)] cursor-pointer">
                            <input type="radio" x-model="listenDateMode" value="none"> No date
                        </label>
                    </div>
                </div>
                <div class="form-group" x-show="listenDateMode === 'exact'">
                    <input type="date" x-model="listenDate" class="form-input"
                           max="{{ today()->format('Y-m-d') }}"
                           x-effect="if (listenOpen && listenDateMode === 'exact') $nextTick(() => $el.focus())">
                </div>
                <div class="form-group" x-show="listenDateMode === 'year'">
                    <input type="number" x-model="listenYear" class="form-input"
                           min="1900" max="{{ today()->year }}" placeholder="{{ today()->year }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Rating (1–10)</label>
                    <input type="number" x-model="listenRating" class="form-input" min="1" max="10" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea x-model="listenNotes" class="form-textarea" rows="3" placeholder="Optional…"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" @click="listenOpen = false" class="btn btn--secondary">Cancel</button>
                <button type="button" @click="addListen()" :disabled="saving" class="btn btn--primary">
                    <span x-show="!saving">Save</span>
                    <span x-show="saving">Saving…</span>
                </button>
            </div>
        </div>
    </div>

</div>

</x-layouts.app>
