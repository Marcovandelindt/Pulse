export function registerGlobalSearch() {
    window.Alpine.data('globalSearch', () => ({
        open: false,
        query: '',
        results: [],
        pageResults: [],
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
            const matches = [];

            items.forEach(el => {
                const hit = !q || el.dataset.searchable.includes(q);
                el.classList.toggle('gs-hidden', !hit);
                if (hit && q) {
                    matches.push({
                        label: el.dataset.label || el.dataset.title || el.dataset.name || '',
                        url:   el.dataset.url || el.href || el.querySelector('a')?.href || '#',
                        img:   el.querySelector('img')?.src || null,
                    });
                }
            });

            this.pageResults     = matches.slice(0, 8);
            this.pageResultCount = q ? matches.length : items.length;
            this.pageTotalCount  = items.length;
            this.selectedIndex   = -1;
        },

        resetPage() {
            document.querySelectorAll('[data-searchable]').forEach(el => {
                el.classList.remove('gs-hidden');
            });
            this.pageResults     = [];
            this.pageResultCount = this.pageTotalCount;
        },

        onKeydown(e) {
            const list = this.pageMode ? this.pageResults : this.results;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, list.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
            } else if (e.key === 'Enter' && this.selectedIndex >= 0) {
                e.preventDefault();
                window.location.href = list[this.selectedIndex].url;
            } else if (e.key === 'Escape') {
                this.close();
            }
        },
    }));
}
