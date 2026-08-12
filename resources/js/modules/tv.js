import { csrf } from '../utils.js';

export function registerTvComponents() {
    Alpine.data('tvIndex', ({ searchUrl, storeUrl }) => ({
        filter: '',
        addOpen: false,
        searchQuery: '',
        searchResults: [],
        searching: false,
        adding: null,

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

    Alpine.data('tvShow', ({ episodeWatches, seasonEpisodeIds, seriesId, seriesName, routes }) => ({
        episodeWatches,
        seasonEpisodeIds,
        openSeasons: {},
        processing: {},
        showAllCast: false,

        watchOpen: false,
        pendingEpisodeId: null,
        pendingEpisodeName: '',
        watchDateMode: 'exact',
        watchDate: new Date().toISOString().slice(0, 10),
        watchYear: String(new Date().getFullYear()),

        bulkOpen: false,
        bulkDateMode: 'year',
        bulkDate: new Date().toISOString().slice(0, 10),
        bulkYear: String(new Date().getFullYear()),

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
            this.watchOpen = true;
        },

        async addWatch() {
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

        async refreshSeries() {
            this.refreshing = true;
            this.$dispatch('toast', { message: 'Refreshing from TMDB…', type: 'success' });
            await fetch(routes.refresh, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            this.$dispatch('toast', { message: 'Series refreshed! Reloading…', type: 'success' });
            setTimeout(() => window.location.reload(), 1200);
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
