export function registerHealthComponents() {
    Alpine.data('goalForm', () => ({
        effectiveFrom: '',

        get goals() {
            return JSON.parse(this.$el.dataset.goals || '[]');
        },

        get periodEnd() {
            if (!this.effectiveFrom) return null;
            const nexts = this.goals
                .filter(d => d > this.effectiveFrom)
                .sort();
            if (nexts.length === 0) return null;
            const d = new Date(nexts[0]);
            d.setUTCDate(d.getUTCDate() - 1);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatDate(s) {
            return new Date(s).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },
    }));
}
