export function registerGlobalSleepMode() {
    Alpine.data('globalSleepMode', ({ sleepItems: rawSleepItems, galleryItems: rawGalleryItems, audioTracks: rawAudioTracks }) => ({
        sleepOpen: false,
        sleepIndex: 0,
        sleepTransitioning: false,
        filter: 'all',
        // Expand items by weight so favorites/recent appear more often
        sleepItems: (rawSleepItems ?? [])
            .flatMap(item => Array(item.weight ?? 1).fill(item))
            .sort(() => Math.random() - 0.5),
        galleryItems: (rawGalleryItems ?? []).slice().sort(() => Math.random() - 0.5),
        sleepTimer: null,
        sleepKeyHandler: null,
        clockTime: '',
        clockDate: '',
        _clockTimer: null,

        audioTracks: rawAudioTracks ?? [],
        audioIndex: 0,
        audioPlaying: false,
        audioVolume: 0.7,
        _audioEl: null,

        get gameItems() {
            return this.sleepItems.filter(i => i.type === 'game');
        },

        get movieItems() {
            return this.sleepItems.filter(i => i.type === 'movie');
        },

        get tvItems() {
            return this.sleepItems.filter(i => i.type === 'tv');
        },

        get currentSleepItems() {
            if (this.filter === 'gallery') return this.galleryItems;
            if (this.filter === 'games')   return this.gameItems;
            if (this.filter === 'movies')  return this.movieItems;
            if (this.filter === 'tv')      return this.tvItems;
            return this.sleepItems;
        },

        get sleepItem() {
            return this.currentSleepItems[this.sleepIndex] ?? null;
        },

        get audioTrack() {
            return this.audioTracks[this.audioIndex] ?? null;
        },

        // Personal milestone: a single stand-out fact about this item
        get milestoneText() {
            const item = this.sleepItem;
            if (!item) return null;

            if (item.type === 'game') {
                if (item.completion >= 100) return `Fully completed`;
                if (item.hours >= 200) return `${item.hours}h played`;
                if (item.hours >= 100) return `Over 100 hours played`;
            }
            if (item.type === 'movie') {
                if (item.watch_count >= 5) return `Watched ${item.watch_count} times`;
                if (item.watch_count >= 3) return `Watched ${item.watch_count} times`;
                if (item.watch_count === 2) return `Watched twice`;
            }
            if (item.type === 'tv') {
                if (item.completion >= 100) return `Completely watched`;
                if (item.completion >= 75) return `${item.completion}% through`;
            }
            return null;
        },

        // Anniversary: fires if first_date is today (any year)
        get anniversaryText() {
            const item = this.sleepItem;
            if (!item?.first_date) return null;

            const first = new Date(item.first_date);
            const today = new Date();

            if (first.getDate() !== today.getDate() || first.getMonth() !== today.getMonth()) return null;

            const years = today.getFullYear() - first.getFullYear();
            if (years <= 0) return null;

            return years === 1 ? `🎂 1 year ago today` : `🎂 ${years} years ago today`;
        },

        init() {
            const tick = () => {
                const now = new Date();
                this.clockTime = now.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                this.clockDate = now.toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'long' });
            };
            tick();
            this._clockTimer = setInterval(tick, 1000);

            if (this.audioTracks.length) {
                this._audioEl = new Audio();
                this._audioEl.volume = this.audioVolume;
                this._audioEl.addEventListener('ended', () => this._audioAdvance());
            }
        },

        destroy() {
            clearInterval(this.sleepTimer);
            clearInterval(this._clockTimer);
            if (this._audioEl) {
                this._audioEl.pause();
                this._audioEl.src = '';
            }
        },

        enterSleep() {
            if (!this.currentSleepItems.length) return;
            this.sleepOpen  = true;
            this.sleepIndex = 0;
            document.body.style.overflow = 'hidden';
            this._preload(this.currentSleepItems);
            this.sleepTimer = setInterval(() => this._sleepNext(), 5000);
            this.sleepKeyHandler = (e) => {
                if (e.key === 'Escape')     this.exitSleep();
                if (e.key === 'ArrowRight') this._sleepNext();
                if (e.key === 'ArrowLeft')  this._sleepPrev();
            };
            document.addEventListener('keydown', this.sleepKeyHandler);

            if (this._audioEl && this.audioTracks.length) {
                this._audioEl.src = this.audioTracks[this.audioIndex].url;
                this._audioEl.play().then(() => { this.audioPlaying = true; }).catch(() => {});
            }
        },

        exitSleep() {
            this.sleepOpen = false;
            document.body.style.overflow = '';
            clearInterval(this.sleepTimer);
            if (this.sleepKeyHandler) {
                document.removeEventListener('keydown', this.sleepKeyHandler);
            }
            if (this._audioEl) {
                this._audioEl.pause();
                this.audioPlaying = false;
            }
        },

        setFilter(f) {
            if (f === 'gallery' && !this.galleryItems.length) return;
            if (f === 'games'   && !this.gameItems.length)   return;
            if (f === 'movies'  && !this.movieItems.length)  return;
            if (f === 'tv'      && !this.tvItems.length)     return;
            this.filter     = f;
            this.sleepIndex = 0;
            this._preload(this.currentSleepItems);
            this._resetSleepTimer();
        },

        audioToggle() {
            if (!this._audioEl) return;
            if (this._audioEl.paused) {
                if (!this._audioEl.src) this._audioEl.src = this.audioTracks[this.audioIndex].url;
                this._audioEl.play().then(() => { this.audioPlaying = true; }).catch(() => {});
            } else {
                this._audioEl.pause();
                this.audioPlaying = false;
            }
        },

        audioNext() {
            if (!this._audioEl || !this.audioTracks.length) return;
            this.audioIndex = (this.audioIndex + 1) % this.audioTracks.length;
            this._audioEl.src = this.audioTracks[this.audioIndex].url;
            if (this.audioPlaying) this._audioEl.play().catch(() => {});
        },

        audioPrev() {
            if (!this._audioEl || !this.audioTracks.length) return;
            this.audioIndex = (this.audioIndex - 1 + this.audioTracks.length) % this.audioTracks.length;
            this._audioEl.src = this.audioTracks[this.audioIndex].url;
            if (this.audioPlaying) this._audioEl.play().catch(() => {});
        },

        audioSetVolume(vol) {
            this.audioVolume = vol;
            if (this._audioEl) this._audioEl.volume = vol;
        },

        _audioAdvance() {
            this.audioIndex = (this.audioIndex + 1) % this.audioTracks.length;
            this._audioEl.src = this.audioTracks[this.audioIndex].url;
            this._audioEl.play().catch(() => {});
        },

        _preload(items) {
            items.forEach(item => {
                if (item.image_url) {
                    const img = new Image();
                    img.src = item.image_url;
                }
            });
        },

        _sleepNext() {
            this.sleepTransitioning = true;
            this._resetSleepTimer();
            setTimeout(() => {
                this.sleepIndex = (this.sleepIndex + 1) % this.currentSleepItems.length;
            }, 350);
            setTimeout(() => { this.sleepTransitioning = false; }, 450);
        },

        _sleepPrev() {
            this.sleepTransitioning = true;
            this._resetSleepTimer();
            setTimeout(() => {
                this.sleepIndex = (this.sleepIndex - 1 + this.currentSleepItems.length) % this.currentSleepItems.length;
            }, 350);
            setTimeout(() => { this.sleepTransitioning = false; }, 450);
        },

        _resetSleepTimer() {
            clearInterval(this.sleepTimer);
            this.sleepTimer = setInterval(() => this._sleepNext(), 5000);
        },
    }));
}
