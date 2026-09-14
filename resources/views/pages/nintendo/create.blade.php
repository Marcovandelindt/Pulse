<x-layouts.app title="Add Switch game">
    <div class="page-header">
        <div class="page-header__left">
            <a href="{{ route('nintendo.index') }}" class="page-header__back">Nintendo Switch</a>
            <h1 class="page-header__title">Add game</h1>
        </div>
    </div>

    <div style="max-width:36rem;">
        <x-ui.card>
            <form
                method="POST"
                action="{{ route('nintendo.store') }}"
                x-data="igdbSearch('{{ route('nintendo.search') }}')"
            >
                @csrf

                {{-- Hidden fields populated by IGDB selection --}}
                <input type="hidden" name="igdb_id" x-model="selected.igdb_id">
                <input type="hidden" name="cover_url" x-model="selected.cover_url">
                <input type="hidden" name="released_at" x-model="selected.released_at">
                <template x-for="genre in selected.genres" :key="genre">
                    <input type="hidden" name="genres[]" :value="genre">
                </template>

                {{-- IGDB search --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label">Search IGDB</label>
                    <div style="position:relative;">
                        <input
                            type="text"
                            class="form-input"
                            placeholder="Type a game name…"
                            x-model="query"
                            @input.debounce.400ms="search"
                            autocomplete="off"
                        >

                        {{-- Dropdown results --}}
                        <div
                            x-show="results.length > 0"
                            x-transition
                            style="position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--color-bg-secondary);border:1px solid var(--color-border);border-radius:var(--radius-md);z-index:40;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.4);"
                        >
                            <template x-for="game in results" :key="game.igdb_id">
                                <button
                                    type="button"
                                    @click="select(game)"
                                    style="display:flex;align-items:center;gap:.75rem;width:100%;padding:.625rem .875rem;text-align:left;background:transparent;border:none;border-bottom:1px solid var(--color-border);cursor:pointer;transition:background var(--transition-base);"
                                    @mouseenter="$el.style.background='var(--color-bg-tertiary)'"
                                    @mouseleave="$el.style.background='transparent'"
                                >
                                    <img
                                        x-show="game.cover_url"
                                        :src="game.cover_url"
                                        style="width:2.5rem;height:2.5rem;border-radius:4px;object-fit:cover;flex-shrink:0;"
                                    >
                                    <div x-show="!game.cover_url" style="width:2.5rem;height:2.5rem;border-radius:4px;background:var(--color-bg-tertiary);flex-shrink:0;"></div>
                                    <div>
                                        <div style="font-size:.875rem;font-weight:500;color:var(--color-text-primary);" x-text="game.name"></div>
                                        <div style="font-size:.75rem;color:var(--color-text-muted);" x-text="game.genres.join(', ') || '—'"></div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Selected preview --}}
                <div x-show="selected.igdb_id" x-transition style="display:flex;align-items:center;gap:1rem;padding:.875rem;background:var(--color-bg-tertiary);border:1px solid var(--color-border);border-radius:var(--radius-md);margin-bottom:1.25rem;">
                    <img
                        x-show="selected.cover_url"
                        :src="selected.cover_url"
                        style="width:3.5rem;height:3.5rem;border-radius:var(--radius-sm);object-fit:cover;flex-shrink:0;"
                    >
                    <div>
                        <div style="font-size:.9375rem;font-weight:600;color:var(--color-text-primary);" x-text="selected.name"></div>
                        <div style="font-size:.8125rem;color:var(--color-text-muted);margin-top:.125rem;" x-text="[selected.genres?.join(', '), selected.released_at ? selected.released_at.substring(0,4) : null].filter(Boolean).join(' · ')"></div>
                    </div>
                    <button type="button" @click="clear()" style="margin-left:auto;color:var(--color-text-muted);background:none;border:none;cursor:pointer;font-size:1.25rem;line-height:1;">×</button>
                </div>

                {{-- Game name (editable, pre-filled by IGDB) --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="name">Game name <span style="color:#ef4444">*</span></label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        class="form-input @error('name') form-input--error @enderror"
                        :value="selected.name || ''"
                        x-ref="nameInput"
                        required
                    >
                    @error('name')
                        <x-form.error>{{ $message }}</x-form.error>
                    @enderror
                </div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="{{ route('nintendo.index') }}" class="btn btn--secondary">Cancel</a>
                    <button type="submit" class="btn btn--primary">Add game</button>
                </div>
            </form>
        </x-ui.card>
    </div>

    <script>
        function igdbSearch(searchUrl) {
            return {
                query:   '',
                results: [],
                selected: {
                    igdb_id:     null,
                    name:        '',
                    cover_url:   null,
                    genres:      [],
                    released_at: null,
                },

                async search() {
                    if (this.query.length < 2) {
                        this.results = [];
                        return;
                    }
                    const res = await fetch(searchUrl + '?q=' + encodeURIComponent(this.query));
                    this.results = await res.json();
                },

                select(game) {
                    this.selected = { ...game };
                    this.$refs.nameInput.value = game.name;
                    this.results = [];
                    this.query   = '';
                },

                clear() {
                    this.selected = { igdb_id: null, name: '', cover_url: null, genres: [], released_at: null };
                    this.$refs.nameInput.value = '';
                },
            };
        }
    </script>
</x-layouts.app>
