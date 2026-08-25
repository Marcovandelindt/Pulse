export function registerGlobalSearch() {
    window.Alpine.data('globalSearch', () => ({
        open: false,
        query: '',
        results: [],
        loading: false,
        selectedIndex: -1,
        pageMode: false,
        pageResultCount: 0,
        pageTotalCount: 0,
        _timer: null,

        init() {
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    const items = document.querySelectorAll('[data-searchable]');
                    this.pageMode = items.length > 0;
                    this.pageTotalCount = items.length;
                    this.pageResultCount = items.length;
                    this.open = true;
                    this.$nextTick(() => this.$refs.input?.focus());
                }
            });
        },

        close() {
            this.open = false;
            this.query = '';
            this.results = [];
            this.selectedIndex = -1;
            if (this.pageMode) {
                this.resetPage();
            }
        },

        onInput() {
            this.selectedIndex = -1;
            clearTimeout(this._timer);

            if (this.pageMode) {
                this.filterPage(this.query);
                return;
            }

            if (!this.query.trim()) {
                this.results = [];
                this.loading = false;
                return;
            }
            this.loading = true;
            this._timer = setTimeout(() => this.fetch(), 180);
        },

        async fetch() {
            const q = this.query.trim();
            if (!q) return;
            try {
                const res = await window.fetch(`/search?q=${encodeURIComponent(q)}`);
                this.results = await res.json();
            } finally {
                this.loading = false;
            }
        },

        filterPage(query) {
            const items = document.querySelectorAll('[data-searchable]');
            const q = query.toLowerCase().trim();
            let visible = 0;
            items.forEach(el => {
                const matches = !q || el.dataset.searchable.includes(q);
                el.classList.toggle('gs-hidden', !matches);
                if (matches) visible++;
            });
            this.pageResultCount = visible;
            this.pageTotalCount = items.length;
        },

        resetPage() {
            document.querySelectorAll('[data-searchable]').forEach(el => {
                el.classList.remove('gs-hidden');
            });
            this.pageResultCount = this.pageTotalCount;
        },

        onKeydown(e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
            } else if (e.key === 'Enter' && this.selectedIndex >= 0) {
                e.preventDefault();
                window.location.href = this.results[this.selectedIndex].url;
            } else if (e.key === 'Escape') {
                this.close();
            }
        },
    }));
}
