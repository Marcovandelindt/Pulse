export function registerPlayStationComponents() {
    Alpine.data('playstationIndex', ({ sleepItems: rawSleepItems, galleryItems: rawGalleryItems }) => ({
        sleepOpen: false,
        sleepIndex: 0,
        sleepTransitioning: false,
        sleepMode: 'standard',
        sleepItems: (rawSleepItems ?? []).slice().sort(() => Math.random() - 0.5),
        galleryItems: (rawGalleryItems ?? []).slice().sort(() => Math.random() - 0.5),
        sleepTimer: null,
        sleepKeyHandler: null,
        clockTime: '',
        clockDate: '',
        _clockTimer: null,

        pickOpen: false,
        pickedGame: null,
        _lastPickedIndex: -1,

        get currentSleepItems() {
            return this.sleepMode === 'gallery' && this.galleryItems.length
                ? this.galleryItems
                : this.sleepItems;
        },

        get sleepItem() {
            return this.currentSleepItems[this.sleepIndex] ?? null;
        },

        pickRandom() {
            const items = this.sleepItems;
            if (!items.length) return;
            let idx;
            do { idx = Math.floor(Math.random() * items.length); }
            while (items.length > 1 && idx === this._lastPickedIndex);
            this._lastPickedIndex = idx;
            this.pickedGame = items[idx];
            this.pickOpen = true;
        },

        closePick() {
            this.pickOpen = false;
        },

        init() {
            const tick = () => {
                const now = new Date();
                this.clockTime = now.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                this.clockDate = now.toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'long' });
            };
            tick();
            this._clockTimer = setInterval(tick, 1000);
        },

        destroy() {
            clearInterval(this.sleepTimer);
            clearInterval(this._clockTimer);
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
        },

        exitSleep() {
            this.sleepOpen = false;
            document.body.style.overflow = '';
            clearInterval(this.sleepTimer);
            if (this.sleepKeyHandler) {
                document.removeEventListener('keydown', this.sleepKeyHandler);
            }
        },

        toggleSleepMode() {
            const next = this.sleepMode === 'standard' ? 'gallery' : 'standard';
            if (next === 'gallery' && !this.galleryItems.length) return;
            this.sleepMode  = next;
            this.sleepIndex = 0;
            this._preload(this.currentSleepItems);
            this._resetSleepTimer();
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
            setTimeout(() => {
                this.sleepTransitioning = false;
            }, 450);
        },

        _sleepPrev() {
            this.sleepTransitioning = true;
            this._resetSleepTimer();
            setTimeout(() => {
                this.sleepIndex = (this.sleepIndex - 1 + this.currentSleepItems.length) % this.currentSleepItems.length;
            }, 350);
            setTimeout(() => {
                this.sleepTransitioning = false;
            }, 450);
        },

        _resetSleepTimer() {
            clearInterval(this.sleepTimer);
            this.sleepTimer = setInterval(() => this._sleepNext(), 5000);
        },
    }));
}
