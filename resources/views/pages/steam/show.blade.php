<x-layouts.app :title="$game->name">

<x-layout.page-header :title="$game->name">
    <x-slot:actions>
        <a href="{{ $game->steam_url }}" target="_blank" rel="noopener"
           class="btn btn--secondary btn--sm">View on Steam ↗</a>
        <a href="{{ route('steam.games.edit', $game) }}" class="btn btn--secondary btn--sm">Edit</a>
        <a href="{{ route('steam.index') }}" class="btn btn--secondary btn--sm">← Back</a>
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <div class="lg:col-span-2 space-y-6">

        <x-ui.card>
            <div class="gaming-hero">
                <div class="gaming-hero__cover" style="width: 7.5rem;">
                    @if($game->image_url)
                        <img src="{{ $game->image_url }}" alt="{{ $game->name }}"
                             class="gaming-hero__img">
                    @else
                        <div style="width: 7.5rem; height: 7.5rem; background: var(--color-bg-tertiary); border-radius: var(--radius-md);"></div>
                    @endif
                </div>

                <div class="gaming-hero__info">
                    <h2 class="text-xl font-bold mb-1" style="color: var(--color-text-primary)">
                        {{ $game->name }}
                    </h2>

                    <div class="flex items-center gap-2 flex-wrap mb-4">
                        @if($game->backlog_status)
                            <span class="badge">
                                {{ $game->backlog_status->icon() }} {{ $game->backlog_status->label() }}
                            </span>
                        @endif
                        @if($game->play_mode)
                            <span class="badge">
                                {{ $game->play_mode->icon() }} {{ $game->play_mode->label() }}
                            </span>
                        @endif
                        @if($game->main_story_completed)
                            <span class="badge" style="background: rgba(34,197,94,0.15); color: #4ade80;">
                                ✅ Story Completed
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Total</div>
                            <div class="text-lg font-bold" style="color: var(--color-text-primary)">{{ $game->formatted_playtime }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Last 2 Weeks</div>
                            <div class="text-lg font-bold" style="color: var(--color-text-primary)">
                                {{ $game->playtime_2weeks_minutes !== null
                                    ? $game->playtime2weeks_hours . 'h'
                                    : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Last Played</div>
                            <div class="text-sm font-medium" style="color: var(--color-text-primary)">
                                {{ $game->last_played_at?->format('d M Y') ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Price</div>
                            <div class="text-lg font-bold" style="color: var(--color-text-primary)">
                                {{ $game->price ? '€ ' . number_format((float) $game->price, 2, ',', '.') : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Statistics">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div>
                    <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Hours Played</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ number_format($game->playtime_hours, 1) }}h</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Minutes Played</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ number_format($game->playtime_minutes) }}</div>
                </div>
                @if($costPerHour !== null)
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Cost / Hour</div>
                        <div class="text-2xl font-bold" style="color: var(--color-text-primary)">€{{ number_format($costPerHour, 2) }}</div>
                        <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ $valueRating }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Cost / Minute</div>
                        <div class="text-lg font-bold" style="color: var(--color-text-primary)">€{{ number_format($costPerMinute, 4) }}</div>
                    </div>
                @endif
                @if($game->user_rating)
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Your Rating</div>
                        <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ $game->user_rating }}<span class="text-sm font-normal" style="color: var(--color-text-muted)">/10</span></div>
                    </div>
                @endif
                @if($game->critic_rating)
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Critic Rating</div>
                        <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ $game->critic_rating }}<span class="text-sm font-normal" style="color: var(--color-text-muted)">/100</span></div>
                    </div>
                @endif
            </div>
        </x-ui.card>

        <div x-data="{
            uploadOpen: {{ $errors->has('image') ? 'true' : 'false' }},
            lightboxOpen: false,
            lightboxIndex: 0,
            lightboxImages: @js($game->images->map(fn ($img) => ['url' => $img->url, 'caption' => $img->caption])->values()),
            openLightbox(index) {
                this.lightboxIndex = index;
                this.lightboxOpen = true;
                document.body.style.overflow = 'hidden';
            },
            closeLightbox() {
                this.lightboxOpen = false;
                document.body.style.overflow = '';
            },
            lightboxNext() {
                this.lightboxIndex = (this.lightboxIndex + 1) % this.lightboxImages.length;
            },
            lightboxPrev() {
                this.lightboxIndex = (this.lightboxIndex - 1 + this.lightboxImages.length) % this.lightboxImages.length;
            },
        }">
        <x-ui.card class="mb-6">
            <x-slot:title>
                Gallery
                <span style="color: var(--color-text-muted); font-weight: 400; font-size: 0.875rem;">{{ $game->images->count() }}</span>
            </x-slot:title>
            <x-slot:action>
                <button @click="uploadOpen = !uploadOpen" class="btn btn--secondary btn--sm">+ Add image</button>
            </x-slot:action>

            <div x-show="uploadOpen" x-cloak class="mb-5 pb-5" style="border-bottom: 1px solid var(--color-border);">
                <form method="POST" action="{{ route('steam.games.gallery.store', $game) }}" enctype="multipart/form-data" class="flex items-end gap-3 flex-wrap">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--color-text-muted);">Image</label>
                        <input type="file" name="image" accept="image/*" required class="form-input text-sm">
                    </div>
                    <div class="flex-1" style="min-width: 12rem;">
                        <label class="block text-xs font-medium mb-1" style="color: var(--color-text-muted);">Caption <span style="opacity:.5">(optional)</span></label>
                        <input type="text" name="caption" maxlength="255" placeholder="e.g. Final boss fight" class="form-input w-full">
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm">Upload</button>
                </form>
                @error('image')
                    <p class="mt-2 text-sm" style="color: #f87171;">{{ $message }}</p>
                @enderror
            </div>

            @if($game->images->isEmpty())
                <p class="text-sm" style="color: var(--color-text-muted);">No images yet. Add screenshots or artwork to build a gallery.</p>
            @else
                <div class="game-gallery">
                    @foreach($game->images as $image)
                        <div class="game-gallery__item">
                            <img src="{{ $image->url }}"
                                 alt="{{ $image->caption ?? $game->name }}"
                                 class="game-gallery__img game-gallery__img--clickable"
                                 @click="openLightbox({{ $loop->index }})">
                            @if($image->caption)
                                <div class="game-gallery__caption">{{ $image->caption }}</div>
                            @endif
                            <form method="POST" action="{{ route('steam.games.gallery.destroy', [$game, $image]) }}" class="game-gallery__delete"
                                  x-data @submit.prevent="if(confirm('Remove this image?')) $el.submit()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Remove">✕</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Lightbox --}}
        <div
            class="lightbox"
            x-show="lightboxOpen"
            x-transition.opacity.duration.200ms
            @keydown.escape.window="closeLightbox()"
            @keydown.arrow-left.window="lightboxPrev()"
            @keydown.arrow-right.window="lightboxNext()"
            style="display:none;"
        >
            <div class="lightbox__backdrop" @click="closeLightbox()"></div>

            <button class="lightbox__close" @click="closeLightbox()">✕</button>

            <button class="lightbox__nav lightbox__nav--prev" @click="lightboxPrev()" x-show="lightboxImages.length > 1" style="display:none;">‹</button>
            <button class="lightbox__nav lightbox__nav--next" @click="lightboxNext()" x-show="lightboxImages.length > 1" style="display:none;">›</button>

            <div class="lightbox__content" @click.stop>
                <img :src="lightboxImages[lightboxIndex]?.url"
                     :alt="lightboxImages[lightboxIndex]?.caption ?? ''"
                     class="lightbox__image">
                <div class="lightbox__footer">
                    <div class="lightbox__caption"
                         x-show="lightboxImages[lightboxIndex]?.caption"
                         x-text="lightboxImages[lightboxIndex]?.caption"
                         style="display:none;"></div>
                    <div class="lightbox__counter"
                         x-show="lightboxImages.length > 1"
                         x-text="`${lightboxIndex + 1} / ${lightboxImages.length}`"
                         style="display:none;"></div>
                </div>
            </div>
        </div>

        </div>

    </div>

    <div class="space-y-6">

        <x-ui.card title="Backlog Status">
            <form method="POST" action="{{ route('gaming.backlog.update', ['type' => 'steam', 'id' => $game->id]) }}">
                @csrf
                @method('PATCH')
                <select name="backlog_status" class="form-input w-full mb-3"
                        onchange="this.form.submit()">
                    <option value="">— Untracked</option>
                    @foreach(\App\Enums\BacklogStatus::cases() as $bs)
                        <option value="{{ $bs->value }}" {{ $game->backlog_status === $bs ? 'selected' : '' }}>
                            {{ $bs->icon() }} {{ $bs->label() }}
                        </option>
                    @endforeach
                </select>
            </form>
        </x-ui.card>

        <x-ui.card title="Genres">
            @if($game->genres->isEmpty())
                <p class="text-sm" style="color: var(--color-text-muted)">No genres set. <a href="{{ route('steam.games.edit', $game) }}" style="color: var(--color-brand)">Edit</a> to add.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($game->genres as $genre)
                        <span class="badge">{{ $genre->name }}</span>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        @if($game->tracks->isNotEmpty())
            <x-ui.card title="Soundtrack">
                <div class="space-y-2">
                    @foreach($game->tracks as $track)
                        <div class="flex items-center gap-3 py-1">
                            @if($track->album?->image_url)
                                <img src="{{ $track->album->image_url }}" alt=""
                                     style="width: 2.25rem; height: 2.25rem; object-fit: cover; border-radius: var(--radius-sm); flex-shrink: 0;">
                            @endif
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('music.tracks.show', $track) }}"
                                   class="text-sm font-medium truncate block hover:underline"
                                   style="color: var(--color-text-primary)">{{ $track->title }}</a>
                                <div class="text-xs truncate" style="color: var(--color-text-muted)">
                                    {{ $track->artists->pluck('name')->implode(', ') }}
                                </div>
                            </div>
                            <span class="text-xs flex-shrink-0" style="color: var(--color-text-muted)">
                                {{ $track->formatted_duration }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        <x-ui.card title="Metadata">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span style="color: var(--color-text-muted)">App ID</span>
                    <span style="color: var(--color-text-primary)">{{ $game->steam_appid }}</span>
                </div>
                <div class="flex justify-between">
                    <span style="color: var(--color-text-muted)">Steam URL</span>
                    <a href="{{ $game->steam_url }}" target="_blank" rel="noopener"
                       style="color: var(--color-brand)">Open ↗</a>
                </div>
                <div class="flex justify-between">
                    <span style="color: var(--color-text-muted)">Added</span>
                    <span style="color: var(--color-text-primary)">{{ $game->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </x-ui.card>

    </div>

</div>

</x-layouts.app>
