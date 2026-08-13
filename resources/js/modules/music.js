import { csrf } from '../utils.js';

export function registerMusicComponents() {
    Alpine.data('musicIndex', ({ searchUrl, storeUrl }) => ({
        filter: '',
        addOpen: false,
        searchQuery: '',
        searchResults: [],
        searching: false,
        adding: null,

        matchesFilter(el) {
            if (!this.filter) return true;
            const q = this.filter.toLowerCase();
            return (el.dataset.title ?? '').toLowerCase().includes(q)
                || (el.dataset.artist ?? '').toLowerCase().includes(q);
        },

        async search() {
            if (!this.searchQuery.trim()) {
                this.searchResults = [];
                return;
            }
            this.searching = true;
            try {
                const res = await fetch(searchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ q: this.searchQuery }),
                });
                this.searchResults = await res.json();
            } finally {
                this.searching = false;
            }
        },

        async addAlbum(spotifyId) {
            if (this.adding) return;
            this.adding = spotifyId;
            try {
                const res = await fetch(storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ spotify_id: spotifyId }),
                });
                const data = await res.json();
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } finally {
                this.adding = null;
            }
        },
    }));

    Alpine.data('musicShow', ({ listens, routes }) => ({
        listens,
        listenOpen: false,
        listenDateMode: 'exact',
        listenDate: new Date().toISOString().slice(0, 10),
        listenYear: String(new Date().getFullYear()),
        listenRating: '',
        listenNotes: '',
        saving: false,
        deleting: null,

        async addListen() {
            if (this.saving) return;
            this.saving = true;

            const body = {
                year_only:   this.listenDateMode === 'year' ? 1 : 0,
                listened_at: this.listenDateMode === 'exact' ? this.listenDate
                           : this.listenDateMode === 'year'  ? this.listenYear + '-01-01'
                           : null,
                rating: this.listenRating || null,
                notes:  this.listenNotes  || null,
            };

            try {
                const res = await fetch(routes.store, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                this.listens.unshift({
                    id:     data.listen_id,
                    date:   data.formatted_date,
                    rating: this.listenRating || null,
                    notes:  this.listenNotes  || null,
                });
                this.listenOpen  = false;
                this.listenRating = '';
                this.listenNotes  = '';
            } finally {
                this.saving = false;
            }
        },

        async deleteListen(id) {
            if (!confirm('Remove this listen?')) return;
            this.deleting = id;
            const url = routes.destroyListen.replace('__ID__', id);
            await fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            this.listens = this.listens.filter(l => l.id !== id);
            this.deleting = null;
        },

        async deleteAlbum() {
            if (!confirm('Remove this album from your collection?')) return;
            await fetch(routes.destroy, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            window.location.href = routes.index;
        },
    }));
}
