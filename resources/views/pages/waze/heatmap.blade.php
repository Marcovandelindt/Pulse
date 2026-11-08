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
        @if($stats['longestDriveId'])
            <div
                class="stat-card stat-card--clickable"
                title="Click to highlight on map"
                onclick="highlightDrive({{ $stats['longestDriveId'] }})"
            >
                <div class="stat-card__icon" style="background: oklch(from var(--color-brand) l c h / 0.15);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-brand-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                </div>
                <div class="stat-card__content">
                    <div class="stat-card__label">Longest drive</div>
                    <div class="stat-card__value">{{ number_format($stats['longestKm'], 1) }} km</div>
                    <div class="stat-card__subtitle">Click to highlight</div>
                </div>
            </div>
        @else
            <x-stats.stat-card
                label="Longest drive"
                :value="number_format($stats['longestKm'], 1) . ' km'"
                icon="map-pin"
            />
        @endif
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

        // driveId → { polyline, points }
        const driveIndex = {};
        let activeHighlight = null;

        function highlightDrive(driveId) {
            // Reset previous highlight
            if (activeHighlight !== null) {
                activeHighlight.setStyle({ color: '#818cf8', weight: 2, opacity: 0.65 });
                activeHighlight = null;
            }

            const entry = driveIndex[driveId];
            if (!entry) return;

            entry.polyline.setStyle({ color: '#f97316', weight: 4, opacity: 1 });
            entry.polyline.bringToFront();
            map.fitBounds(entry.polyline.getBounds(), { padding: [60, 60] });
            activeHighlight = entry.polyline;

            // Make sure routes layer is visible
            if (!map.hasLayer(routeLayer)) {
                routeLayer.addTo(map);
            }
        }

        fetch('{{ route('waze.heatmap.data') }}')
            .then(r => r.json())
            .then(routes => {
                const heatPoints = [];

                routes.forEach(({ driveId, points }) => {
                    const polyline = L.polyline(points, { color: '#818cf8', weight: 2, opacity: 0.65 }).addTo(routeLayer);

                    if (driveId !== null) {
                        driveIndex[driveId] = { polyline, points };
                    }

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
