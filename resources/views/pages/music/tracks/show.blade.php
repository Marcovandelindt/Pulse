<x-layouts.app :title="$track->title">

    {{-- Hero backdrop: primary artist photo --}}
    @if($heroImage)
        <div class="media-hero media-hero--blur" style="--hero-img: url('{{ $heroImage }}')">
            <div class="media-hero__overlay"></div>
        </div>
    @endif

    <div class="media-detail">

        <div class="media-detail__main">

            @if($track->album?->image_url)
                <div class="media-detail__poster-wrap">
                    <img src="{{ $track->album->image_url }}" alt="{{ $track->album->name }}" class="media-detail__poster">
                </div>
            @endif

            <div class="media-detail__info">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="media-detail__title">{{ $track->title }}</h1>
                        <div class="media-detail__original-title">
                            @foreach($track->artists as $artist)
                                <a href="{{ route('music.artists.show', $artist) }}" class="hover:underline" style="color: inherit;">{{ $artist->name }}</a>
                                @if(! $loop->last)<span> · </span>@endif
                            @endforeach
                            @if($track->album)
                                <span> · </span>
                                <a href="{{ route('music.albums.show', $track->album) }}" class="hover:underline" style="color: inherit;">{{ $track->album->name }}</a>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('music.tracks.obsession', $track) }}">
                            @csrf
                            <button type="submit" class="btn btn--{{ $track->is_obsession ? 'primary' : 'secondary' }} btn--sm">
                                {{ $track->is_obsession ? '★ Obsession' : '☆ Mark as obsession' }}
                            </button>
                        </form>
                        <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
                    </div>
                </div>

                <div
                    class="media-detail__genres"
                    x-data="{
                        genres: @js($track->genres ?? []),
                        input: '',
                        saving: false,
                        add() {
                            const val = this.input.trim().toLowerCase();
                            if (val && !this.genres.includes(val)) {
                                this.genres.push(val);
                                this.save();
                            }
                            this.input = '';
                        },
                        remove(genre) {
                            this.genres = this.genres.filter(g => g !== genre);
                            this.save();
                        },
                        async save() {
                            this.saving = true;
                            await fetch('{{ route('music.tracks.update', $track) }}', {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({ genres: this.genres }),
                            });
                            this.saving = false;
                        },
                    }"
                >
                    <template x-for="genre in genres" :key="genre">
                        <span class="badge badge--muted" style="cursor:default;">
                            <span x-text="genre"></span>
                            <button @click="remove(genre)" class="badge-remove" type="button" title="Remove">&times;</button>
                        </span>
                    </template>
                    <input
                        type="text"
                        x-model="input"
                        @keydown.enter.prevent="add()"
                        @keydown.comma.prevent="add()"
                        placeholder="Add genre…"
                        class="genre-input"
                    >
                    <span x-show="saving" class="genre-saving">saving…</span>
                </div>

                <div class="media-detail__meta-row">
                    @if($track->is_explicit)
                        <span class="explicit-badge">E</span>
                    @endif
                    @if($track->formatted_duration)
                        <span>{{ $track->formatted_duration }}</span>
                    @endif
                    @if($track->album?->release_year)
                        <span>{{ $track->album->release_year }}</span>
                    @endif
                </div>

                <div class="media-detail__stats-row">
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value">{{ number_format($playCount) }}</div>
                        <div class="media-detail__stat-label">total plays</div>
                    </div>
                    @if($firstPlay)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ $firstPlay->played_at->format('d M Y') }}</div>
                            <div class="media-detail__stat-label">first played</div>
                        </div>
                    @endif
                    @if($lastPlay)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ $lastPlay->played_at->format('d M Y') }}</div>
                            <div class="media-detail__stat-label">last played</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <x-ui.card class="mt-6">
            <x-slot:title>Linked Game</x-slot:title>

            @if($track->game)
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        @if($track->game->image_url)
                            <img src="{{ $track->game->image_url }}" alt=""
                                 style="width: 2.5rem; height: 2.5rem; object-fit: cover; border-radius: var(--radius-sm); flex-shrink: 0;">
                        @endif
                        <div>
                            @if($track->gameable_type === 'playstation')
                                <a href="{{ route('playstation.show', $track->game) }}"
                                   class="font-medium hover:underline" style="color: var(--color-text-primary)">
                                    {{ $track->game->label }}
                                </a>
                                <div class="text-xs mt-0.5" style="color: var(--color-text-muted)">
                                    PlayStation · {{ $track->game->platform }}
                                </div>
                            @else
                                <a href="{{ route('steam.games.show', $track->game) }}"
                                   class="font-medium hover:underline" style="color: var(--color-text-primary)">
                                    {{ $track->game->name }}
                                </a>
                                <div class="text-xs mt-0.5" style="color: var(--color-text-muted)">Steam</div>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('music.tracks.game.destroy', $track) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--secondary btn--sm">Unlink</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('music.tracks.game.update', $track) }}"
                      x-data="{ type: '' }"
                      class="flex gap-3 items-end flex-wrap">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="gameable_type" x-model="type">
                    <div class="flex-1" style="min-width: 16rem;">
                        <label class="form-label" for="gameable_id">Link to a game</label>
                        <select
                            name="gameable_id"
                            id="gameable_id"
                            class="form-input"
                            required
                            @change="type = $event.target.selectedOptions[0]?.dataset.type ?? ''"
                        >
                            <option value="">— Select a game —</option>
                            @if($playstationGames->isNotEmpty())
                                <optgroup label="PlayStation">
                                    @foreach($playstationGames as $psGame)
                                        <option value="{{ $psGame->id }}" data-type="playstation">
                                            {{ $psGame->display_name ?? $psGame->name }} ({{ $psGame->platform }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($steamGames->isNotEmpty())
                                <optgroup label="Steam">
                                    @foreach($steamGames as $steamGame)
                                        <option value="{{ $steamGame->id }}" data-type="steam">
                                            {{ $steamGame->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                    <button type="submit" class="btn btn--primary" :disabled="!type">Link</button>
                </form>
            @endif
        </x-ui.card>

        <x-ui.card title="Play history" class="mt-6">
            @if($track->plays->isEmpty())
                <x-ui.empty-state title="No plays recorded" />
            @else
                <div class="play-list">
                    @foreach($track->plays->sortByDesc('played_at') as $play)
                        <div class="play-item">
                            <div class="play-item__info">
                                <div class="play-item__title">{{ $play->played_at->format('D, d M Y') }}</div>
                                <div class="play-item__meta">{{ $play->played_at->format('H:i') }}</div>
                            </div>
                            <span class="play-item__time">{{ $play->played_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>

    <x-layout.notification />

</x-layouts.app>
