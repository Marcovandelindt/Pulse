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
    <script>
        const map = L.map('location-map').setView([52.3, 4.9], 12);

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

        const motionLabels = {
            driving: 'Driving',
            walking: 'Walking',
            cycling: 'Cycling',
            other: 'Other',
        };

        const mapLayers = {};
        let bounds = null;

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
                    const color = motionColors[item.dominant_motion] ?? motionColors.other;
                    const label = motionLabels[item.dominant_motion] ?? item.dominant_motion;
                    return `
                        <div class="location-segment location-segment--trip" data-id="${item.id}" onclick="highlightLayer('trip-${item.id}')">
                            <div class="location-segment__dot" style="background:${color}"></div>
                            <div class="location-segment__body">
                                <div class="location-segment__title">${label}</div>
                                <div class="location-segment__meta">${item.started_at}–${item.ended_at} &middot; ${item.distance_km} km &middot; ${formatDuration(item.duration_minutes)}</div>
                            </div>
                            <div class="location-segment__badge" style="background:${color}20;color:${color}">${item.distance_km} km</div>
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

        fetch(`{{ route('location.data') }}?date={{ $date }}`)
            .then(r => r.json())
            .then(({ trips, visits }) => {
                buildTimeline(trips, visits);

                const allPoints = [];

                trips.forEach(trip => {
                    if (!trip.polyline || trip.polyline.length < 2) return;
                    const color = motionColors[trip.dominant_motion] ?? motionColors.other;
                    const line = L.polyline(trip.polyline, { color, weight: 2.5, opacity: 0.75 }).addTo(map);
                    mapLayers['trip-' + trip.id] = line;
                    trip.polyline.forEach(p => allPoints.push(p));
                });

                visits.forEach(visit => {
                    if (!visit.lat || !visit.lon) return;
                    const marker = L.circleMarker([visit.lat, visit.lon], {
                        radius: 7,
                        fillColor: '#38bdf8',
                        color: '#0ea5e9',
                        weight: 2,
                        fillOpacity: 0.9,
                        opacity: 0.9,
                    }).addTo(map);
                    mapLayers['visit-' + visit.id] = marker;
                    allPoints.push([visit.lat, visit.lon]);
                });

                if (allPoints.length > 0) {
                    map.fitBounds(L.latLngBounds(allPoints), { padding: [30, 30] });
                }
            });
    </script>

    <script>
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
