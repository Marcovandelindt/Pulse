export function registerRecommendationComponents() {
    Alpine.data('recommendations', ({ mode, allPeople, actorBaseUrl }) => ({
        showActorSearch: mode === 'actor',
        actorSearch: '',
        showDropdown: false,
        showMovies: true,
        showSeries: true,
        showWatched: true,
        showUnwatched: true,

        get filteredPeople() {
            if (this.actorSearch.length < 1) return [];
            const q = this.actorSearch.toLowerCase();
            return allPeople.filter(p => p.name.toLowerCase().includes(q)).slice(0, 8);
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
