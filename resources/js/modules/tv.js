import { csrf } from '../utils.js';

export function registerTvComponents() {
    Alpine.data('tvIndex', ({ searchUrl, storeUrl, backdrops: rawBackdrops, sleepItems: rawSleepItems }) => ({
        filter: '',
        addOpen: false,
        searchQuery: '',
        searchResults: [],
        searching: false,
        adding: null,

        backdrops: rawBackdrops.slice().sort(() => Math.random() - 0.5),
        currentSlide: 0,
        _timer: null,
        clockTime: '',
        clockDate: '',
        _clockTimer: null,

        init() {
            if (this.backdrops.length > 1) {
                this._timer = setInterval(() => {
                    this.currentSlide = (this.currentSlide + 1) % this.backdrops.length;
                }, 2500);
            }
            const tick = () => {
                const now = new Date();
                this.clockTime = now.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                this.clockDate = now.toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'long' });
            };
            tick();
            this._clockTimer = setInterval(tick, 1000);
        },

        destroy() {
            clearInterval(this._timer);
            clearInterval(this.sleepTimer);
            clearInterval(this._clockTimer);
        },

        sleepOpen: false,
        sleepIndex: 0,
        sleepTransitioning: false,
        sleepItems: (rawSleepItems ?? []).slice().sort(() => Math.random() - 0.5),
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

        async addSeries(tmdbId) {
            this.adding = tmdbId;
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ tmdb_id: tmdbId }),
            });
            const data = await res.json();
            this.adding = null;
            this.searchResults = this.searchResults.map(r =>
                r.tmdb_id === tmdbId ? { ...r, already_added: true } : r
            );
            this.$dispatch('toast', { message: `${data.name} added!`, type: 'success' });
            setTimeout(() => window.location.reload(), 1000);
        },

        matchesFilter(el) {
            if (!this.filter) return true;
            const q = this.filter.toLowerCase();
            return (el.dataset.name ?? '').toLowerCase().includes(q)
                || (el.dataset.original ?? '').toLowerCase().includes(q)
                || (el.dataset.nameEn ?? '').toLowerCase().includes(q);
        },
    }));

    Alpine.data('tvShow', ({ episodeWatches, seasonEpisodeIds, seriesId, seriesName, userRating, isFavorite, routes }) => ({
        episodeWatches,
        seasonEpisodeIds,
        openSeasons: {},
        processing: {},
        showAllCast: false,

        isFavorite,

        async toggleFavorite() {
            this.isFavorite = !this.isFavorite;
            await fetch(routes.favorite, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
        },

        userRating,
        ratingInput: userRating ?? '',
        ratingEditing: false,

        async saveRating() {
            this.ratingEditing = false;
            const value = this.ratingInput === '' ? null : parseFloat(this.ratingInput);
            const res = await fetch(routes.rating, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ rating: value }),
            });
            const data = await res.json();
            this.userRating = data.user_rating;
            this.ratingInput = data.user_rating ?? '';
            this.$dispatch('toast', { message: 'Rating saved!', type: 'success' });
        },

        watchOpen: false,
        watchUpTo: false,
        pendingEpisodeId: null,
        pendingEpisodeName: '',
        watchDateMode: 'exact',
        watchDate: new Date().toISOString().slice(0, 10),
        watchYear: String(new Date().getFullYear()),

        bulkOpen: false,
        bulkDateMode: 'year',
        bulkDate: new Date().toISOString().slice(0, 10),
        bulkYear: String(new Date().getFullYear()),

        seasonBulkOpen: false,
        pendingSeasonId: null,
        pendingSeasonName: '',
        seasonBulkDateMode: 'year',
        seasonBulkDate: new Date().toISOString().slice(0, 10),
        seasonBulkYear: String(new Date().getFullYear()),

        refreshing: false,

        init() {
            const stored = localStorage.getItem(`openSeasons_${seriesId}`);
            if (stored) {
                try { this.openSeasons = JSON.parse(stored); } catch (e) {}
            }
        },

        toggleSeason(id) {
            this.openSeasons[id] = !this.openSeasons[id];
            localStorage.setItem(`openSeasons_${seriesId}`, JSON.stringify(this.openSeasons));
        },
        isSeasonOpen(id) { return !!this.openSeasons[id]; },

        isWatched(id) { return (this.episodeWatches[id] ?? []).length > 0; },
        watchesForEpisode(id) { return this.episodeWatches[id] ?? []; },

        watchedInSeason(seasonId) {
            return (this.seasonEpisodeIds[seasonId] ?? []).filter(id => this.isWatched(id)).length;
        },

        openAddWatch(episodeId, episodeName) {
            this.pendingEpisodeId   = episodeId;
            this.pendingEpisodeName = episodeName;
            this.watchUpTo = false;
            this.watchOpen = true;
        },

        openUpToWatch(episodeId, episodeName) {
            this.pendingEpisodeId   = episodeId;
            this.pendingEpisodeName = episodeName;
            this.watchUpTo = true;
            this.watchOpen = true;
        },

        async addWatch() {
            if (this.watchUpTo) {
                await this._addWatchUpTo();
                return;
            }

            const episodeId = this.pendingEpisodeId;
            if (!episodeId) return;
            this.watchOpen = false;
            this.processing[episodeId] = true;

            let watchedAt = null;
            let yearOnly  = false;
            if (this.watchDateMode === 'exact') {
                watchedAt = this.watchDate;
            } else if (this.watchDateMode === 'year') {
                watchedAt = this.watchYear + '-01-01';
                yearOnly  = true;
            }

            try {
                const res = await fetch(`/tv/episodes/${episodeId}/watches`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ watched_at: watchedAt, year_only: yearOnly }),
                });
                const data = await res.json();
                if (!this.episodeWatches[episodeId]) this.episodeWatches[episodeId] = [];
                this.episodeWatches[episodeId].push({ id: data.watch_id, date: data.formatted_date });
                this.$dispatch('toast', { message: 'Watch saved!', type: 'success' });
            } finally {
                this.processing[episodeId] = false;
                this.pendingEpisodeId = null;
            }
        },

        async _addWatchUpTo() {
            const episodeId = this.pendingEpisodeId;
            if (!episodeId) return;
            this.watchOpen = false;

            let watchedAt = null;
            let yearOnly  = false;
            if (this.watchDateMode === 'exact') {
                watchedAt = this.watchDate;
            } else if (this.watchDateMode === 'year') {
                watchedAt = this.watchYear + '-01-01';
                yearOnly  = true;
            }

            const res = await fetch(`/tv/episodes/${episodeId}/watches/bulk-up-to`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ watched_at: watchedAt, year_only: yearOnly }),
            });
            const data = await res.json();
            this.$dispatch('toast', { message: `${data.episodes_watched} episodes marked as watched!`, type: 'success' });
            setTimeout(() => window.location.reload(), 1500);
        },

        async deleteWatch(watchId, episodeId) {
            await fetch(`/tv/watches/${watchId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            this.episodeWatches[episodeId] = (this.episodeWatches[episodeId] ?? []).filter(w => w.id !== watchId);
            this.$dispatch('toast', { message: 'Watch removed', type: 'success' });
        },

        async bulkWatch() {
            if (!confirm(`Mark ALL episodes of ${seriesName} as watched?`)) return;
            this.bulkOpen = false;

            let watchedAt = null;
            let yearOnly  = false;
            if (this.bulkDateMode === 'exact') {
                watchedAt = this.bulkDate;
            } else if (this.bulkDateMode === 'year') {
                watchedAt = this.bulkYear + '-01-01';
                yearOnly  = true;
            }

            const res = await fetch(routes.bulk, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ watched_at: watchedAt, year_only: yearOnly }),
            });
            const data = await res.json();
            this.$dispatch('toast', { message: `Marked ${data.episodes_watched} episodes as watched!`, type: 'success' });
            setTimeout(() => window.location.reload(), 1500);
        },

        openBulkWatchSeason(seasonId, seasonName) {
            this.pendingSeasonId   = seasonId;
            this.pendingSeasonName = seasonName;
            this.seasonBulkOpen    = true;
        },

        async bulkWatchSeason() {
            this.seasonBulkOpen = false;

            let watchedAt = null;
            let yearOnly  = false;
            if (this.seasonBulkDateMode === 'exact') {
                watchedAt = this.seasonBulkDate;
            } else if (this.seasonBulkDateMode === 'year') {
                watchedAt = this.seasonBulkYear + '-01-01';
                yearOnly  = true;
            }

            const res = await fetch(`/tv/seasons/${this.pendingSeasonId}/watches/bulk`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ watched_at: watchedAt, year_only: yearOnly }),
            });
            const data = await res.json();
            this.$dispatch('toast', { message: `${data.episodes_watched} episodes marked as watched!`, type: 'success' });
            setTimeout(() => window.location.reload(), 1500);
        },

        async refreshSeries() {
            this.refreshing = true;
            this.$dispatch('toast', { message: 'Refreshing from TMDB…', type: 'success' });
            try {
                const res = await fetch(routes.refresh, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf() },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                this.$dispatch('toast', { message: 'Series refreshed! Reloading…', type: 'success' });
                setTimeout(() => window.location.reload(), 1200);
            } catch (e) {
                this.refreshing = false;
                this.$dispatch('toast', { message: 'Refresh failed. Please try again.', type: 'error' });
            }
        },

        async removeSeries() {
            if (!confirm('Remove this series and all watch history?')) return;
            await fetch(routes.destroy, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            window.location.href = routes.index;
        },
    }));
}
