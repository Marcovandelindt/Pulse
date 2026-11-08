<x-layouts.app title="Waze">

    <x-layout.page-header title="Waze Drives">
        <x-slot:actions>
            <a href="{{ route('waze.import') }}" class="btn btn--secondary btn--sm">Import archive</a>
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
                </tr>
            </thead>
            <tbody>
                @foreach($drives as $drive)
                    <tr>
                        <td class="table__cell--muted text-sm whitespace-nowrap">
                            {{ $drive->driven_at->setTimezone('Europe/Amsterdam')->format('d M Y, H:i') }}
                        </td>
                        <td class="text-sm">{{ $drive->origin_name ?? '—' }}</td>
                        <td class="text-sm">{{ $drive->destination_name ?? '—' }}</td>
                        <td class="text-right text-sm whitespace-nowrap">
                            @if($drive->distance_km)
                                {{ $drive->distance_km }} km
                            @else
                                —
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
