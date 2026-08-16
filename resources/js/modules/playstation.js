export function registerPlayStationComponents() {
    Alpine.data('playstationIndex', ({ sleepItems: rawSleepItems }) => ({
        sleepOpen: false,
        sleepIndex: 0,
        sleepTransitioning: false,
        sleepItems: (rawSleepItems ?? []).slice().sort(() => Math.random() - 0.5),
        sleepTimer: null,
        sleepKeyHandler: null,
        clockTime: '',
        clockDate: '',
        _clockTimer: null,

        get sleepItem() {
            return this.sleepItems[this.sleepIndex] ?? null;
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
            if (!this.sleepItems.length) return;
            this.sleepOpen  = true;
            this.sleepIndex = 0;
            document.body.style.overflow = 'hidden';
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

        _sleepNext() {
            this.sleepTransitioning = true;
            this._resetSleepTimer();
            setTimeout(() => {
                this.sleepIndex = (this.sleepIndex + 1) % this.sleepItems.length;
                this.sleepTransitioning = false;
            }, 400);
        },

        _sleepPrev() {
            this.sleepTransitioning = true;
            this._resetSleepTimer();
            setTimeout(() => {
                this.sleepIndex = (this.sleepIndex - 1 + this.sleepItems.length) % this.sleepItems.length;
                this.sleepTransitioning = false;
            }, 400);
        },

        _resetSleepTimer() {
            clearInterval(this.sleepTimer);
            this.sleepTimer = setInterval(() => this._sleepNext(), 5000);
        },
    }));
}
