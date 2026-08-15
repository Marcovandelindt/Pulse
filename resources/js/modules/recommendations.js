export function registerRecommendationComponents() {
    Alpine.data('recommendations', ({ mode, actorBaseUrl, searchUrl }) => ({
        showActorSearch: mode === 'actor',
        actorSearch: '',
        showDropdown: false,
        searchResults: [],
        _debounce: null,
        showMovies: true,
        showSeries: true,
        showWatched: true,
        showUnwatched: true,

        async searchActors() {
            clearTimeout(this._debounce);
            if (this.actorSearch.length < 2) {
                this.searchResults = [];
                this.showDropdown = false;
                return;
            }
            this._debounce = setTimeout(async () => {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(this.actorSearch)}`);
                this.searchResults = await res.json();
                this.showDropdown = this.searchResults.length > 0;
            }, 200);
        },

        openActorSearch() {
            this.showActorSearch = true;
            this.$nextTick(() => this.$refs.actorInput?.focus());
        },

        selectActor(person) {
            window.location.href = actorBaseUrl + '/' + person.id;
        },

        toggleMovies() {
            if (!this.showSeries) return;
            this.showMovies = !this.showMovies;
        },

        toggleSeries() {
            if (!this.showMovies) return;
            this.showSeries = !this.showSeries;
        },

        toggleWatched() {
            if (!this.showUnwatched) return;
            this.showWatched = !this.showWatched;
        },

        toggleUnwatched() {
            if (!this.showWatched) return;
            this.showUnwatched = !this.showUnwatched;
        },
    }));
}
