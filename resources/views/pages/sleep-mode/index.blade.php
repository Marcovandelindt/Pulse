<x-layouts.app title="Sleep Mode">

<div x-data="globalSleepMode({ sleepItems: @js($sleepItems), galleryItems: @js($galleryItems), audioTracks: @js($audioTracks) })">

    {{-- Entry screen --}}
    <div x-show="!sleepOpen">
        <x-layout.page-header title="Sleep Mode" />

        <div class="flex flex-col items-center justify-center gap-5 py-16">
            <div style="font-size: 4rem; line-height: 1;">☾</div>
            <p style="color: var(--color-text-muted); font-size: 0.9375rem;">
                {{ count($sleepItems) }} items across all libraries
                @if(count($galleryItems) > 0)
                    · {{ count($galleryItems) }} gallery shots
                @endif
            </p>
            <button @click="enterSleep()" class="btn btn--primary" style="font-size: 1rem; padding: 0.75rem 2rem; display: none;" x-show="sleepItems.length > 0">
                Enter Sleep Mode
            </button>
            <p x-show="sleepItems.length === 0" style="color: var(--color-text-muted); font-size: 0.875rem; display: none;">
                No items found. Add games, movies, or TV shows to get started.
            </p>
        </div>
    </div>

    {{-- Sleep Mode overlay --}}
    <div class="sleep-mode" x-show="sleepOpen" :class="{ 'sleep-mode--gallery': filter === 'gallery' }" style="display:none;">
        <div class="sleep-mode__backdrop"
             :style="sleepItem ? `background-image: url('${sleepItem.image_url}')` : ''">
        </div>

        <div class="sleep-mode__fade" :class="{ 'sleep-mode__fade--active': sleepTransitioning }"></div>

        <button class="sleep-mode__close" @click="exitSleep()">✕</button>

        <div class="sleep-mode__modes">
            <button class="sleep-mode__mode-btn" :class="{ 'sleep-mode__mode-btn--active': filter === 'all' }" @click="setFilter('all')">All</button>
            <button class="sleep-mode__mode-btn" :class="{ 'sleep-mode__mode-btn--active': filter === 'games' }" @click="setFilter('games')" x-show="gameItems.length > 0" style="display:none;">Games</button>
            <button class="sleep-mode__mode-btn" :class="{ 'sleep-mode__mode-btn--active': filter === 'movies' }" @click="setFilter('movies')" x-show="movieItems.length > 0" style="display:none;">Movies</button>
            <button class="sleep-mode__mode-btn" :class="{ 'sleep-mode__mode-btn--active': filter === 'tv' }" @click="setFilter('tv')" x-show="tvItems.length > 0" style="display:none;">TV</button>
            <button class="sleep-mode__mode-btn" :class="{ 'sleep-mode__mode-btn--active': filter === 'gallery' }" @click="setFilter('gallery')" x-show="galleryItems.length > 0" style="display:none;">Gallery</button>
        </div>

        <button class="sleep-mode__nav sleep-mode__nav--prev" @click="_sleepPrev()">‹</button>
        <button class="sleep-mode__nav sleep-mode__nav--next" @click="_sleepNext()">›</button>

        {{-- Standard mode (all, games, movies, tv) --}}
        <div class="sleep-mode__clock" x-show="filter !== 'gallery'">
            <div class="sleep-mode__clock-time" x-text="clockTime"></div>
            <div class="sleep-mode__clock-date" x-text="clockDate"></div>
        </div>

        <div class="sleep-mode__content" x-show="filter !== 'gallery'">
            <div class="sleep-mode__poster-wrap">
                <img :src="sleepItem?.image_url" :alt="sleepItem?.title" class="sleep-mode__poster">
            </div>
            <div class="sleep-mode__info">
                <div class="sleep-mode__category" x-text="sleepItem?.category"></div>
                <div class="sleep-mode__title" x-text="sleepItem?.title"></div>
                <div class="sleep-mode__meta" x-text="sleepItem?.meta"></div>

                {{-- Anniversary badge --}}
                <div class="sleep-mode__anniversary" x-show="anniversaryText" style="display:none;"
                     x-effect="sleepIndex; $el.style.animation = 'none'; $el.offsetWidth; $el.style.animation = ''">
                    <span x-text="anniversaryText"></span>
                </div>

                {{-- Game stats --}}
                <div class="sleep-mode__stats" x-show="sleepItem?.type === 'game'">
                    <div x-show="sleepItem?.hours > 0">
                        <div class="sleep-mode__stat-label">Hours played</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.hours + 'h'"></div>
                    </div>
                    <div x-show="sleepItem?.completion > 0">
                        <div class="sleep-mode__stat-label">Completion</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.completion + '%'"></div>
                        <div class="sleep-mode__bar-wrap">
                            <div class="sleep-mode__bar" :style="`width: ${sleepItem?.completion ?? 0}%`"></div>
                        </div>
                    </div>
                    <div x-show="sleepItem?.last_played">
                        <div class="sleep-mode__stat-label">Last played</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.last_played"></div>
                    </div>
                </div>

                {{-- Movie stats --}}
                <div class="sleep-mode__stats" x-show="sleepItem?.type === 'movie'">
                    <div x-show="sleepItem?.genres">
                        <div class="sleep-mode__stat-label">Genres</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.genres"></div>
                    </div>
                    <div x-show="sleepItem?.watch_count > 0">
                        <div class="sleep-mode__stat-label">Watched</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.watch_count + '×'"></div>
                    </div>
                    <div x-show="sleepItem?.last_watched">
                        <div class="sleep-mode__stat-label">Last watched</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.last_watched"></div>
                    </div>
                </div>

                {{-- TV stats --}}
                <div class="sleep-mode__stats" x-show="sleepItem?.type === 'tv'">
                    <div x-show="sleepItem?.genres">
                        <div class="sleep-mode__stat-label">Genres</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.genres"></div>
                    </div>
                    <div x-show="sleepItem?.episodes">
                        <div class="sleep-mode__stat-label">Episodes</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.episodes"></div>
                    </div>
                    <div x-show="sleepItem?.completion > 0">
                        <div class="sleep-mode__stat-label">Progress</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.completion + '%'"></div>
                        <div class="sleep-mode__bar-wrap">
                            <div class="sleep-mode__bar" :style="`width: ${sleepItem?.completion ?? 0}%`"></div>
                        </div>
                    </div>
                    <div x-show="sleepItem?.last_watched">
                        <div class="sleep-mode__stat-label">Last watched</div>
                        <div class="sleep-mode__stat" x-text="sleepItem?.last_watched"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gallery mode --}}
        <div class="sleep-mode__gallery-image-wrap" x-show="filter === 'gallery'" style="display:none;">
            <img :src="sleepItem?.image_url" :alt="sleepItem?.game_title" class="sleep-mode__gallery-image">
        </div>

        <div class="sleep-mode__gallery-info" x-show="filter === 'gallery'" style="display:none;">
            <div class="sleep-mode__gallery-clock">
                <span x-text="clockTime"></span>
                <span class="sleep-mode__gallery-clock-date" x-text="clockDate"></span>
            </div>
            <div class="sleep-mode__gallery-caption-wrap">
                <div class="sleep-mode__gallery-game" x-text="sleepItem?.game_title"></div>
                <div class="sleep-mode__gallery-caption" x-show="sleepItem?.caption" x-text="sleepItem?.caption"></div>
            </div>
        </div>

        {{-- Audio controls --}}
        <div class="sleep-mode__audio" x-show="audioTracks.length > 0" style="display:none;">
            <button class="sleep-mode__audio-btn" x-show="audioTracks.length > 1" style="display:none;" @click="audioPrev()">&#9664;</button>
            <button class="sleep-mode__audio-btn sleep-mode__audio-btn--play" @click="audioToggle()">
                <span x-show="!audioPlaying">&#9654;</span>
                <span x-show="audioPlaying" style="display:none;">&#9646;&#9646;</span>
            </button>
            <button class="sleep-mode__audio-btn" x-show="audioTracks.length > 1" style="display:none;" @click="audioNext()">&#9654;</button>
            <div class="sleep-mode__audio-track">
                <span x-text="audioTrack?.title ?? ''"></span>
                <span x-show="audioTrack?.game_name" style="display:none; opacity:.55;" x-text="audioTrack?.game_name ? ' · ' + audioTrack.game_name : ''"></span>
            </div>
            <input type="range"
                min="0" max="1" step="0.05"
                :value="audioVolume"
                @input="audioSetVolume(parseFloat($event.target.value))"
                class="sleep-mode__audio-volume"
                title="Volume"
            >
        </div>

        {{-- Milestone overlay (fades in 2s after each item, standard mode only) --}}
        <div class="sleep-mode__milestone" x-show="milestoneText && filter !== 'gallery'" style="display:none;"
             x-effect="sleepIndex; $el.style.animation = 'none'; $el.offsetWidth; $el.style.animation = ''">
            <span x-text="milestoneText"></span>
        </div>

        <div class="sleep-mode__counter" x-text="`${sleepIndex + 1} / ${currentSleepItems.length}`"></div>

        <div class="sleep-progress">
            <div class="sleep-progress__fill"
                 x-effect="sleepIndex; $el.style.animation = 'none'; $el.offsetWidth; $el.style.animation = ''"></div>
        </div>
    </div>

</div>

</x-layouts.app>
