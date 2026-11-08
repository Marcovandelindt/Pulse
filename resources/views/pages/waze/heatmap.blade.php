<x-layouts.app title="Waze Heatmap">

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @endpush

    <x-layout.page-header title="Drive Heatmap">
        <x-slot:actions>
            <a href="{{ route('waze.index') }}" class="btn btn--secondary btn--sm">← Drives</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5 mb-6">
        <x-stats.stat-card
            label="Total distance"
            :value="number_format($stats['totalKm'], 1) . ' km'"
            icon="map-pin"
        />
        <x-stats.stat-card
            label="Drives"
            :value="number_format($stats['totalDrives'])"
            icon="flag"
        />
        <x-stats.stat-card
            label="Behind the wheel"
            :value="number_format($stats['totalHours'], 1) . ' hrs'"
            icon="clock"
        />
        <x-stats.stat-card
            label="Netherlands explored"
            :value="$stats['discoveredPercent'] . '%'"
            icon="globe"
        />
        <x-stats.stat-card
            label="Longest drive"
            :value="number_format($stats['longestKm'], 1) . ' km'"
            icon="map-pin"
        />
    </div>

    <div class="waze-heatmap-wrap">
        <div id="waze-heatmap" class="waze-heatmap"></div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script>
        const map = L.map('waze-heatmap');

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        const routeLayer = L.layerGroup().addTo(map);
        const heatGroup = L.layerGroup();

        fetch('{{ route('waze.heatmap.data') }}')
            .then(r => r.json())
            .then(routes => {
                const heatPoints = [];

                routes.forEach(points => {
                    L.polyline(points, { color: '#818cf8', weight: 2, opacity: 0.65 }).addTo(routeLayer);
                    points.forEach(p => heatPoints.push([p[0], p[1], 1]));
                });

                L.heatLayer(heatPoints, {
                    radius: 8,
                    blur: 12,
                    maxZoom: 17,
                    gradient: { 0.3: '#fc4c02', 0.6: '#ff8c00', 1.0: '#ffffff' },
                }).addTo(heatGroup);

                L.control.layers({}, { 'Routes': routeLayer, 'Heatmap': heatGroup }, {
                    collapsed: false,
                    position: 'topright',
                }).addTo(map);

                if (heatPoints.length > 0) {
                    map.fitBounds(
                        L.latLngBounds(heatPoints.map(p => [p[0], p[1]])),
                        { padding: [30, 30] }
                    );
                } else {
                    map.setView([52.3, 4.9], 8);
                }
            });
    </script>
    @endpush

</x-layouts.app>
