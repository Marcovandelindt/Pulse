<x-layouts.app title="Location Heatmap">

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @endpush

    <x-layout.page-header title="Location Heatmap">
        <x-slot:actions>
            <a href="{{ route('location.index') }}" class="btn btn--secondary btn--sm">← Day view</a>
            <a href="{{ route('location.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="location-heatmap-controls" x-data="{
        from: '{{ now()->subDays(30)->toDateString() }}',
        to: '{{ now()->toDateString() }}',
        load() {
            loadMap(this.from, this.to);
        }
    }" x-init="load()">
        <label class="location-heatmap-controls__label">From</label>
        <input type="date" x-model="from" class="location-datepicker__input">
        <label class="location-heatmap-controls__label">To</label>
        <input type="date" x-model="to" class="location-datepicker__input">
        <button @click="load()" class="btn btn--primary btn--sm">Apply</button>
    </div>

    <div class="location-heatmap-wrap">
        <div id="location-heatmap"></div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script>
        const map = L.map('location-heatmap').setView([52.3, 4.9], 10);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        const motionColors = {
            driving: '#818cf8',
            walking: '#4ade80',
            cycling: '#fbbf24',
            other: '#94a3b8',
        };

        let heatLayer = null;
        let routeLayer = L.layerGroup().addTo(map);
        let heatGroup = L.layerGroup();
        let layerControl = null;

        function loadMap(from, to) {
            routeLayer.clearLayers();
            heatGroup.clearLayers();
            if (heatLayer) { heatLayer = null; }

            const url = `{{ route('location.heatmap.data') }}?from=${from}&to=${to}`;

            fetch(url)
                .then(r => r.json())
                .then(({ points, routes }) => {
                    const heatPoints = points.map(p => [p[0], p[1], 1]);

                    routes.forEach(route => {
                        if (!route.polyline || route.polyline.length < 2) return;
                        const color = motionColors[route.dominant_motion] ?? motionColors.other;
                        L.polyline(route.polyline, { color, weight: 2, opacity: 0.65 }).addTo(routeLayer);
                    });

                    if (heatPoints.length > 0) {
                        heatLayer = L.heatLayer(heatPoints, {
                            radius: 10,
                            blur: 15,
                            maxZoom: 17,
                            gradient: { 0.3: '#3b82f6', 0.6: '#a855f7', 1.0: '#f97316' },
                        }).addTo(heatGroup);

                        const allBounds = L.latLngBounds(heatPoints.map(p => [p[0], p[1]]));
                        map.fitBounds(allBounds, { padding: [30, 30] });
                    }

                    if (layerControl) { layerControl.remove(); }
                    layerControl = L.control.layers({}, {
                        'Routes': routeLayer,
                        'Heatmap': heatGroup,
                    }, { collapsed: false, position: 'topright' }).addTo(map);
                });
        }
    </script>
    @endpush

</x-layouts.app>
