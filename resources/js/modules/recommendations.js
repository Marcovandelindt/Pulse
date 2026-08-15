export function registerRecommendationComponents() {
    Alpine.data('recommendations', ({ mode, actorBaseUrl, searchUrl }) => ({
        showActorSearch: mode === 'actor',
        actorSearch: '',
        showDropdown: false,
        searchResults: [],
        debounceTimer: null,
        showMovies: true,
        showSeries: true,
        showWatched: true,
        showUnwatched: true,

        init() {
            this.$watch('actorSearch', (value) => {
                clearTimeout(this.debounceTimer);

                if (value.length < 2) {
                    this.searchResults = [];
                    this.showDropdown = false;
                    return;
                }

                this.debounceTimer = setTimeout(async () => {
                    try {
                        const res = await fetch(`${searchUrl}?q=${encodeURIComponent(value)}`);
                        this.searchResults = await res.json();
                        this.showDropdown = this.searchResults.length > 0;
                    } catch {
                        this.searchResults = [];
                        this.showDropdown = false;
                    }
                }, 200);
            });
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
