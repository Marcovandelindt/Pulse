import { csrf } from '../utils.js';

export function registerMovieComponents() {
    Alpine.data('movieIndex', ({ searchUrl, storeUrl }) => ({
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

        async addMovie(tmdbId) {
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
            this.$dispatch('toast', { message: `${data.title} added!`, type: 'success' });
            setTimeout(() => window.location.reload(), 1000);
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
