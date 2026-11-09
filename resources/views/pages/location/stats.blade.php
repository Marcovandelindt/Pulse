<x-layouts.app title="Location Stats">

    <x-layout.page-header title="Location Stats">
        <x-slot:actions>
            <a href="{{ route('location.index') }}" class="btn btn--secondary btn--sm">← Day view</a>
            <a href="{{ route('location.heatmap') }}" class="btn btn--secondary btn--sm">Heatmap</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Period selector --}}
    <div class="location-stats-period">
        @foreach(['week' => 'This week', 'month' => 'This month', '3months' => 'Last 3 months', 'year' => 'This year', 'all' => 'All time'] as $key => $label)
            <a href="{{ route('location.stats', ['period' => $key]) }}"
               class="location-stats-period__btn {{ $period === $key ? 'location-stats-period__btn--active' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Overview stats --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
        <x-stats.stat-card
            label="Total distance"
            :value="$totalKm . ' km'"
            icon="map-pin"
        />
        <x-stats.stat-card
            label="Trips"
            :value="(string) $totalTrips"
            icon="arrow-right"
        />
        <x-stats.stat-card
            label="Visits"
            :value="(string) $totalVisits"
            icon="clock"
        />
        <x-stats.stat-card
            label="Unique places"
            :value="(string) $uniquePlaces"
            icon="flag"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Distance by motion --}}
        <x-ui.card title="Distance by motion type">
            @if(empty($byMotion))
                <p class="text-[var(--color-text-muted)] text-sm">No data for this period.</p>
            @else
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Motion</th>
                            <th class="text-right">Distance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byMotion as $motion => $km)
                            <tr>
                                <td>
                                    <span class="location-stats-motion location-stats-motion--{{ $motion }}">{{ ucfirst($motion) }}</span>
                                </td>
                                <td class="text-right font-semibold">{{ $km }} km</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-ui.card>

        {{-- Top places --}}
        <x-ui.card title="Most visited places">
            @if($topPlaces->isEmpty())
                <p class="text-[var(--color-text-muted)] text-sm">No places recorded yet.</p>
            @else
                <ul class="location-places-list">
                    @foreach($topPlaces as $place)
                        <li class="location-places-list__item">
                            <span class="location-places-list__name">{{ $place->name ?? 'Unnamed place' }}</span>
                            <span class="location-places-list__count">{{ $place->visit_count }} visit{{ $place->visit_count !== 1 ? 's' : '' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

    </div>

</x-layouts.app>
