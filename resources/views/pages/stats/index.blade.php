<x-layouts.app title="Stats">

    @php
        $yearStart = \Illuminate\Support\Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd   = $year === now()->year
            ? now()->startOfDay()
            : \Illuminate\Support\Carbon::create($year, 12, 31)->endOfDay();
    @endphp

    <x-layout.page-header
        title="Stats"
        subtitle="A year of activity at a glance."
    >
        <x-slot:actions>
            @if (count($availableYears) > 1)
                <form method="GET" action="{{ route('stats.index') }}">
                    <select
                        name="year"
                        class="form-input form-input--sm"
                        onchange="this.form.submit()"
                        style="width: auto; cursor: pointer;"
                    >
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    <div class="stats-heatmaps">

        <x-stats.heatmap
            label="Steps"
            :entries="$stepsData"
            unit="steps"
            scheme="green"
            :start="$yearStart"
            :end="$yearEnd"
        />

        <x-stats.heatmap
            label="Gaming"
            :entries="$gamingData"
            unit="minutes"
            scheme="purple"
            :format="fn($v) => ($v >= 60 ? intdiv($v, 60) . 'h ' . ($v % 60) . 'm' : $v . 'm') . ' played'"
            :start="$yearStart"
            :end="$yearEnd"
        />

        <x-stats.heatmap
            label="Music"
            :entries="$musicData"
            unit="tracks"
            scheme="pink"
            :start="$yearStart"
            :end="$yearEnd"
        />

        <x-stats.heatmap
            label="Media"
            :entries="$mediaData"
            unit="episodes / films"
            scheme="amber"
            :start="$yearStart"
            :end="$yearEnd"
        />

    </div>

</x-layouts.app>
