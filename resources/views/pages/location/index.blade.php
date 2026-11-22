<x-layouts.app title="Location">

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @endpush

    <x-layout.page-header title="Location">
        <x-slot:actions>
            <a href="{{ route('location.heatmap') }}" class="btn btn--secondary btn--sm">Heatmap</a>
            <a href="{{ route('location.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Date navigation --}}
    <div class="location-datepicker">
        <a href="{{ route('location.index', ['date' => $prevDate]) }}" class="btn btn--secondary btn--sm btn--icon" title="Previous day">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <form method="GET" action="{{ route('location.index') }}" class="location-datepicker__form">
            <input type="date" name="date" value="{{ $date }}" class="location-datepicker__input" onchange="this.form.submit()">
        </form>
        <a href="{{ route('location.index', ['date' => $nextDate]) }}" class="btn btn--secondary btn--sm btn--icon {{ $isToday ? 'btn--disabled' : '' }}" title="Next day">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>

    <div class="location-day">
        <div class="location-day__map">
            <div id="location-map"></div>
        </div>

        <div class="location-day__sidebar">
            <div id="location-timeline" class="location-timeline">
                <div class="location-timeline__empty">Loading…</div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script>
        const map = L.map('location-map').setView([52.3, 4.9], 12);

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ',
            maxZoom: 16,
        }).addTo(map);

        const motionColors = {
            driving: '#818cf8',
            walking: '#4ade80',
            cycling: '#fbbf24',
            other: '#94a3b8',
        };

        const motionLabels = {
            driving: 'Driving',
            walking: 'Walking',
            cycling: 'Cycling',
            other: 'Other',
        };

        const mapLayers = {};
        let bounds = null;
        const tripUpdateBase = '{{ route('location.trips.update', ':id') }}';

        function formatDuration(minutes) {
            if (minutes < 60) return minutes + ' min';
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return m > 0 ? h + 'h ' + m + 'min' : h + 'h';
        }

        function highlightLayer(id) {
            Object.entries(mapLayers).forEach(([layerId, layer]) => {
                const isActive = layerId === String(id);
                if (layer.setStyle) {
                    layer.setStyle({ opacity: isActive ? 1 : 0.35, weight: isActive ? 4 : 2 });
                } else if (layer.setOpacity) {
                    layer.setOpacity(isActive ? 1 : 0.4);
                }
            });
        }

        function resetHighlight() {
            Object.values(mapLayers).forEach(layer => {
                if (layer.setStyle) layer.setStyle({ opacity: 0.75, weight: 2.5 });
                else if (layer.setOpacity) layer.setOpacity(0.9);
            });
        }

        function buildTimeline(trips, visits) {
            const timeline = document.getElementById('location-timeline');

            if (trips.length === 0 && visits.length === 0) {
                timeline.innerHTML = '<div class="location-timeline__empty">No data for this day.</div>';
                return;
            }

            const all = [
                ...trips.map(t => ({ ...t, sortKey: t.started_at })),
                ...visits.map(v => ({ ...v, sortKey: v.started_at })),
            ].sort((a, b) => a.sortKey.localeCompare(b.sortKey));

            timeline.innerHTML = all.map(item => {
                if (item.type === 'trip') {
                    const updateUrl = tripUpdateBase.replace(':id', item.id);
                    return `
                        <div class="location-segment location-segment--trip" data-id="${item.id}"
                             x-data="tripMotionEditor(${item.id}, '${item.dominant_motion}', '${updateUrl}')"
                             @click="!editing && highlightLayer('trip-${item.id}')">
                            <div class="location-segment__dot" :style="'background:' + color"></div>
                            <div class="location-segment__body">
                                <div class="location-segment__title">
                                    <span x-show="!editing" @click.stop="editing = true" class="location-segment__place-name" title="Click to change">
                                        <span x-text="label"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="location-segment__edit-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                        </svg>
                                    </span>
                                    <div x-show="editing" @click.stop class="location-segment__motion-picker">
                                        <button @click="save('driving')" class="location-segment__motion-btn" :class="{ 'location-segment__motion-btn--active': motion === 'driving' }">Driving</button>
                                        <button @click="save('walking')" class="location-segment__motion-btn" :class="{ 'location-segment__motion-btn--active': motion === 'walking' }">Walking</button>
                                        <button @click="save('cycling')" class="location-segment__motion-btn" :class="{ 'location-segment__motion-btn--active': motion === 'cycling' }">Cycling</button>
                                        <button @click="save('other')" class="location-segment__motion-btn" :class="{ 'location-segment__motion-btn--active': motion === 'other' }">Other</button>
                                        <button @click="editing = false" class="location-segment__motion-btn location-segment__motion-btn--cancel">✕</button>
                                    </div>
                                </div>
                                <div class="location-segment__meta">${item.started_at}–${item.ended_at} &middot; ${item.distance_km} km &middot; ${formatDuration(item.duration_minutes)}</div>
                            </div>
                            <div class="location-segment__badge" :style="'background:' + color + '20;color:' + color">${item.distance_km} km</div>
                        </div>`;
                } else {
                    const placeName = item.place_name ?? 'Unknown place';
                    const editId = item.place_id ? `place-${item.place_id}` : null;
                    const editUrl = item.place_id ? `{{ route('location.places.update', ':id') }}`.replace(':id', item.place_id) : null;
                    return `
                        <div class="location-segment location-segment--visit" data-id="${item.id}" onclick="highlightLayer('visit-${item.id}')">
                            <div class="location-segment__dot location-segment__dot--visit"></div>
                            <div class="location-segment__body">
                                ${editUrl ? `
                                <div x-data="placeEditor(${item.place_id}, '${placeName.replace(/'/g, "\\'")}', '${editUrl}')" class="location-segment__title">
                                    <span x-show="!editing" @click.stop="startEdit()" class="location-segment__place-name" title="Click to rename">
                                        <span x-text="name"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="location-segment__edit-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                        </svg>
                                    </span>
                                    <form x-show="editing" @submit.prevent="save()" @click.stop class="location-segment__edit-form">
                                        <input x-ref="nameInput" x-model="editValue" class="location-segment__edit-input" @keydown.escape="editing = false">
                                        <button type="submit" class="btn btn--primary btn--sm">Save</button>
                                        <button type="button" class="btn btn--secondary btn--sm" @click="editing = false">Cancel</button>
                                    </form>
                                </div>` : `<div class="location-segment__title">${placeName}</div>`}
                                <div class="location-segment__meta">${item.started_at}–${item.ended_at} &middot; ${formatDuration(item.duration_minutes)}</div>
                            </div>
                        </div>`;
                }
            }).join('');
        }

        function interpolateLine(polyline, stepDeg) {
            const result = [];
            for (let i = 0; i < polyline.length - 1; i++) {
                const [lat1, lng1] = polyline[i];
                const [lat2, lng2] = polyline[i + 1];
                const dist  = Math.hypot(lat2 - lat1, lng2 - lng1);
                const steps = Math.max(1, Math.round(dist / stepDeg));
                for (let s = 0; s < steps; s++) {
                    const t = s / steps;
                    result.push([lat1 + (lat2 - lat1) * t, lng1 + (lng2 - lng1) * t]);
                }
            }
            if (polyline.length > 0) result.push(polyline[polyline.length - 1]);
            return result;
        }

        fetch(`{{ route('location.data') }}?date={{ $date }}`)
            .then(r => r.json())
            .then(({ trips, visits }) => {
                buildTimeline(trips, visits);

                const heatPoints = [];
                const allPoints  = [];
                const routeLayer = L.layerGroup();

                trips.forEach(trip => {
                    if (!trip.polyline || trip.polyline.length < 2) return;
                    interpolateLine(trip.polyline, 0.0002).forEach(p => {
                        allPoints.push(p);
                        heatPoints.push([p[0], p[1], 0.4]);
                    });
                    const color = motionColors[trip.dominant_motion] ?? motionColors.other;
                    const line  = L.polyline(trip.polyline, { color, weight: 3, opacity: 0.85 });
                    mapLayers['trip-' + trip.id] = line;
                    routeLayer.addLayer(line);
                });

                visits.forEach(visit => {
                    if (!visit.lat || !visit.lon) return;
                    allPoints.push([visit.lat, visit.lon]);
                    heatPoints.push([visit.lat, visit.lon, 1.0]);
                    heatPoints.push([visit.lat, visit.lon, 1.0]);
                    heatPoints.push([visit.lat, visit.lon, 1.0]);
                    const marker = L.circleMarker([visit.lat, visit.lon], {
                        radius: 6, fillColor: '#fc4c02', color: '#ea580c',
                        weight: 2, fillOpacity: 0.85, opacity: 0.85,
                    });
                    mapLayers['visit-' + visit.id] = marker;
                    routeLayer.addLayer(marker);
                });

                const heatLayer = heatPoints.length > 0
                    ? L.heatLayer(heatPoints, {
                        radius: 12, blur: 15, maxZoom: 17, max: 1.0,
                        gradient: { 0.2: '#fc4c02', 0.55: '#ff8c00', 1.0: '#fff' },
                    }).addTo(map)
                    : null;

                L.control.layers(
                    {},
                    { 'Heatmap': heatLayer, 'Routes': routeLayer },
                    { collapsed: false, position: 'topright' }
                ).addTo(map);

                if (allPoints.length > 0) {
                    map.fitBounds(L.latLngBounds(allPoints), { padding: [30, 30] });
                }
            });
    </script>

    <script>
        function tripMotionEditor(tripId, initialMotion, updateUrl) {
            return {
                motion: initialMotion,
                editing: false,
                labels: { driving: 'Driving', walking: 'Walking', cycling: 'Cycling', other: 'Other' },
                colors: { driving: '#818cf8', walking: '#4ade80', cycling: '#fbbf24', other: '#94a3b8' },
                get label() { return this.labels[this.motion] ?? this.motion; },
                get color() { return this.colors[this.motion] ?? '#94a3b8'; },
                async save(newMotion) {
                    const res = await fetch(updateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({ dominant_motion: newMotion }),
                    });
                    if (res.ok) this.motion = newMotion;
                    this.editing = false;
                },
            };
        }

        function placeEditor(placeId, initialName, updateUrl) {
            return {
                name: initialName,
                editValue: initialName,
                editing: false,
                startEdit() {
                    this.editValue = this.name;
                    this.editing = true;
                    this.$nextTick(() => this.$refs.nameInput.focus());
                },
                async save() {
                    const res = await fetch(updateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({ name: this.editValue }),
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.name = data.name ?? this.editValue;
                    }
                    this.editing = false;
                },
            };
        }
    </script>
    @endpush

</x-layouts.app>
