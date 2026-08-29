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

            @if($linkedGame)
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        @if($linkedGame->image_url)
                            <img src="{{ $linkedGame->image_url }}" alt=""
                                 style="width: 2.5rem; height: 2.5rem; object-fit: cover; border-radius: var(--radius-sm); flex-shrink: 0;">
                        @endif
                        <div>
                            @if($track->gameable_type === 'playstation')
                                <a href="{{ route('playstation.show', $linkedGame) }}"
                                   class="font-medium hover:underline" style="color: var(--color-text-primary)">
                                    {{ $linkedGame->label }}
                                </a>
                                <div class="text-xs mt-0.5" style="color: var(--color-text-muted)">
                                    PlayStation · {{ $linkedGame->platform }}
                                </div>
                            @else
                                <a href="{{ route('steam.games.show', $linkedGame) }}"
                                   class="font-medium hover:underline" style="color: var(--color-text-primary)">
                                    {{ $linkedGame->name }}
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
                      x-data="{
                          search: '',
                          open: false,
                          selectedId: null,
                          selectedType: '',
                          games: @js(
                              $playstationGames->map(fn ($g) => [
                                  'id'    => $g->id,
                                  'type'  => 'playstation',
                                  'label' => ($g->display_name ?? $g->name) . ' (' . $g->platform . ')',
                                  'group' => 'PlayStation',
                              ])->concat($steamGames->map(fn ($g) => [
                                  'id'    => $g->id,
                                  'type'  => 'steam',
                                  'label' => $g->name,
                                  'group' => 'Steam',
                              ]))
                          ),
                          get filtered() {
                              if (!this.search.trim()) return this.games;
                              const q = this.search.toLowerCase();
                              return this.games.filter(g => g.label.toLowerCase().includes(q));
                          },
                          get grouped() {
                              const map = {};
                              this.filtered.forEach(g => {
                                  if (!map[g.group]) map[g.group] = [];
                                  map[g.group].push(g);
                              });
                              return Object.entries(map).map(([name, items]) => ({ name, items }));
                          },
                          select(game) {
                              this.selectedId = game.id;
                              this.selectedType = game.type;
                              this.search = game.label;
                              this.open = false;
                          },
                      }"
                      @submit.prevent="if (selectedId) $el.submit()"
                      class="flex gap-3 items-end flex-wrap">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="gameable_id" :value="selectedId ?? ''">
                    <input type="hidden" name="gameable_type" :value="selectedType">
                    <div class="flex-1" style="min-width: 18rem; position: relative;" @click.outside="open = false">
                        <label class="form-label">Link to a game</label>
                        <input
                            type="text"
                            x-model="search"
                            @focus="open = true"
                            @input="open = true; selectedId = null; selectedType = ''"
                            @keydown.escape="open = false"
                            @keydown.enter.prevent
                            placeholder="Search games…"
                            class="form-input"
                            autocomplete="off"
                        >
                        <div x-show="open && grouped.length > 0"
                             style="position: absolute; z-index: 50; left: 0; right: 0; top: calc(100% + 4px);
                                    background: var(--color-bg-secondary); border: 1px solid var(--color-border);
                                    border-radius: var(--radius-md); max-height: 260px; overflow-y: auto;
                                    box-shadow: var(--shadow-card);">
                            <template x-for="group in grouped" :key="group.name">
                                <div>
                                    <div x-text="group.name"
                                         style="padding: 0.375rem 0.75rem; font-size: 0.6875rem; font-weight: 600;
                                                text-transform: uppercase; letter-spacing: 0.05em;
                                                color: var(--color-text-muted); position: sticky; top: 0;
                                                background: var(--color-bg-secondary); border-bottom: 1px solid var(--color-border);"></div>
                                    <template x-for="game in group.items" :key="game.id + game.type">
                                        <div x-text="game.label"
                                             @click="select(game)"
                                             style="padding: 0.5rem 0.75rem; cursor: pointer; font-size: 0.875rem;
                                                    color: var(--color-text-primary); transition: background var(--transition-base);"
                                             @mouseenter="$el.style.background = 'var(--color-bg-tertiary)'"
                                             @mouseleave="$el.style.background = ''"></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div x-show="open && search.trim() && grouped.length === 0"
                             style="position: absolute; z-index: 50; left: 0; right: 0; top: calc(100% + 4px);
                                    background: var(--color-bg-secondary); border: 1px solid var(--color-border);
                                    border-radius: var(--radius-md); padding: 0.75rem;
                                    font-size: 0.875rem; color: var(--color-text-muted);">
                            No games found.
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary" :disabled="!selectedId">Link</button>
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
