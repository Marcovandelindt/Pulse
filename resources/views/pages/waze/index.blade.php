<x-layouts.app title="Waze">

    <x-layout.page-header title="Waze Drives">
        <x-slot:actions>
            <a href="{{ route('waze.import') }}" class="btn btn--secondary btn--sm">Import archive</a>
            @if(!$drives->isEmpty())
                <form method="POST" action="{{ route('waze.destroy-all') }}"
                      x-data
                      @submit.prevent="confirm('Delete all drives? This cannot be undone.') && $el.submit()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger btn--sm">Clear all</button>
                </form>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if($drives->isEmpty())
        <x-ui.empty-state
            title="No drives yet"
            description="Import your Waze data archive to get started."
        >
            <a href="{{ route('waze.import') }}" class="btn btn--primary btn--sm">Import archive</a>
        </x-ui.empty-state>
    @else

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 mb-6">
        <x-stats.stat-card
            label="Drives this month"
            :value="$stats['drivesThisMonth']"
            icon="map"
        />
        <x-stats.stat-card
            label="KM this month"
            :value="$stats['kmThisMonth'] . ' km'"
            icon="arrow-right"
        />
        <x-stats.stat-card
            label="Most visited"
            :value="$stats['topDestination'] ?? '—'"
            icon="map-pin"
        />
    </div>

    @if($stats['perMonth']->isNotEmpty())
    <x-ui.card title="Drives per month" class="mb-6">
        <canvas
            data-chart="bar"
            data-chart-data="{{ json_encode([
                'labels' => $stats['perMonth']->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y')),
                'values' => $stats['perMonth']->pluck('total'),
            ]) }}"
            style="max-height: 220px;"
        ></canvas>
    </x-ui.card>
    @endif

    <x-ui.card title="Drives">
        <x-slot:action>
            {{ $drives->links() }}
        </x-slot:action>

        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-right">Distance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($drives as $drive)
                    @php $needsGeocode = ($drive->origin_name === null && $drive->origin_lat !== null) || ($drive->destination_name === null && $drive->destination_lat !== null); @endphp
                    <tr>
                        <td class="table__cell--muted text-sm whitespace-nowrap">
                            {{ $drive->driven_at->setTimezone('Europe/Amsterdam')->format('d M Y, H:i') }}
                        </td>
                        <td class="text-sm">
                            @if($drive->origin_name)
                                {{ $drive->origin_name }}
                            @elseif($drive->origin_lat)
                                <span class="table__cell--muted text-xs">{{ round($drive->origin_lat, 4) }}, {{ round($drive->origin_lon, 4) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-sm">
                            @if($drive->destination_name)
                                {{ $drive->destination_name }}
                            @elseif($drive->destination_lat)
                                <span class="table__cell--muted text-xs">{{ round($drive->destination_lat, 4) }}, {{ round($drive->destination_lon, 4) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right text-sm whitespace-nowrap">
                            @if($drive->distance_km)
                                {{ $drive->distance_km }} km
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right">
                            @if($needsGeocode)
                                <form method="POST" action="{{ route('waze.geocode', $drive) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn--secondary btn--sm btn--icon" title="Look up address">
                                        <x-icon name="map-pin" class="w-3.5 h-3.5" />
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-ui.card>

    @endif

    <x-layout.notification />

</x-layouts.app>
