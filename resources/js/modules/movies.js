import { csrf } from '../utils.js';

export function registerMovieComponents() {
    Alpine.data('movieIndex', ({ searchUrl, storeUrl, backdrops: rawBackdrops, sleepItems: rawSleepItems }) => ({
        filter: '',
        addOpen: false,
        searchQuery: '',
        searchResults: [],
        searching: false,
        adding: null,

        backdrops: rawBackdrops.slice().sort(() => Math.random() - 0.5),
        currentSlide: 0,
        _timer: null,

        init() {
            if (this.backdrops.length > 1) {
                this._timer = setInterval(() => {
                    this.currentSlide = (this.currentSlide + 1) % this.backdrops.length;
                }, 2500);
            }
        },

        destroy() {
            clearInterval(this._timer);
            clearInterval(this.sleepTimer);
        },

        sleepOpen: false,
        sleepIndex: 0,
        sleepTransitioning: false,
        sleepItems: rawSleepItems ?? [],
        sleepTimer: null,
        sleepKeyHandler: null,

        get sleepItem() {
            return this.sleepItems[this.sleepIndex] ?? null;
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
            }, 250);
        },

        _sleepPrev() {
            this.sleepTransitioning = true;
            this._resetSleepTimer();
            setTimeout(() => {
                this.sleepIndex = (this.sleepIndex - 1 + this.sleepItems.length) % this.sleepItems.length;
                this.sleepTransitioning = false;
            }, 250);
        },

        _resetSleepTimer() {
            clearInterval(this.sleepTimer);
            this.sleepTimer = setInterval(() => this._sleepNext(), 5000);
        },

        async search() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            this.searching = true;
            const res = await fetch(searchUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ query: this.searchQuery }),
            });
            const data = await res.json();
            this.searchResults = data.results ?? [];
            this.searching = false;
        },

        async addMovie(tmdbId) {
            this.adding = tmdbId;
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ tmdb_id: tmdbId }),
            });
            const data = await res.json();
            this.adding = null;
            this.$dispatch('toast', { message: `${data.title} added!`, type: 'success' });
            setTimeout(() => window.location.href = data.show_url, 800);
        },

        matchesFilter(el) {
            if (!this.filter) return true;
            const q = this.filter.toLowerCase();
            return (el.dataset.title ?? '').toLowerCase().includes(q)
                || (el.dataset.originalTitle ?? '').toLowerCase().includes(q);
        },
    }));

    Alpine.data('movieShow', ({ watches, routes }) => ({
        watches,
        watchOpen: false,
        watchDateMode: 'exact',
        watchDate: new Date().toISOString().slice(0, 10),
        watchYear: String(new Date().getFullYear()),
        watchRating: '',
        watchNotes: '',
        showAllCast: false,

        async addWatch() {
            let watchedAt = null;
            let yearOnly  = false;
            if (this.watchDateMode === 'exact') {
                watchedAt = this.watchDate;
            } else if (this.watchDateMode === 'year') {
                watchedAt = this.watchYear + '-01-01';
                yearOnly  = true;
            }

            const res = await fetch(routes.store, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({
                    watched_at: watchedAt,
                    year_only:  yearOnly,
                    rating:     this.watchRating || null,
                    notes:      this.watchNotes  || null,
                }),
            });
            const data = await res.json();
            this.watches.unshift({
                id:     data.watch_id,
                date:   data.formatted_date,
                rating: this.watchRating || null,
                notes:  this.watchNotes  || null,
            });
            this.watchOpen   = false;
            this.watchRating = '';
            this.watchNotes  = '';
            this.$dispatch('toast', { message: 'Watch saved!', type: 'success' });
        },

        async deleteWatch(watchId) {
            if (!confirm('Delete this watch?')) return;
            await fetch(`/movies/watches/${watchId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            this.watches = this.watches.filter(w => w.id !== watchId);
            this.$dispatch('toast', { message: 'Watch deleted', type: 'success' });
        },

        async refreshMovie() {
            if (!confirm('Refresh cast and crew from TMDB?')) return;
            await fetch(routes.refresh, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            window.location.reload();
        },

        async deleteMovie() {
            if (!confirm('Remove this movie and all watch history?')) return;
            await fetch(routes.destroy, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            window.location.href = routes.index;
        },
    }));
}
