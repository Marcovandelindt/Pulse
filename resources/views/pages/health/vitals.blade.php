<x-layouts.app title="Vitals">

    <x-layout.page-header title="Vitals" />

    <x-health.nav />

    <p class="health-page-intro">
        Core physiological measurements recorded by Apple Watch throughout the day and night.
        These numbers reflect how your cardiovascular system and body are performing at rest —
        a lower resting heart rate and higher HRV generally indicate better cardiovascular fitness and recovery.
    </p>

    {{-- Stat cards --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Resting HR (latest)"
            :value="$latest?->resting_heart_rate ? $latest->resting_heart_rate . ' bpm' : '—'"
        />
        <x-stats.stat-card
            label="Avg resting HR"
            :value="$avgRestingHr ? (int) round($avgRestingHr) . ' bpm' : '—'"
        />
        <x-stats.stat-card
            label="HRV (latest)"
            :value="$latest?->hrv ? round((float) $latest->hrv, 1) . ' ms' : '—'"
        />
        <x-stats.stat-card
            label="Avg HRV"
            :value="$avgHrv ? round((float) $avgHrv, 1) . ' ms' : '—'"
        />
    </div>

    <div class="stats-row" style="margin-top: 0.75rem;">
        <x-stats.stat-card
            label="Respiratory rate (latest)"
            :value="$latest?->respiratory_rate ? round((float) $latest->respiratory_rate, 1) . ' /min' : '—'"
        />
        <x-stats.stat-card
            label="Avg respiratory rate"
            :value="$avgRespiratoryRate ? round((float) $avgRespiratoryRate, 1) . ' /min' : '—'"
        />
        <x-stats.stat-card
            label="Weight (latest)"
            :value="$latestWeight?->weight_kg ? $latestWeight->weight_kg . ' kg' : '—'"
        />
        <x-stats.stat-card
            label="Weight recorded"
            :value="$latestWeight ? $latestWeight->date->format('d M Y') : '—'"
        />
    </div>

    {{-- Resting HR trend --}}
    <x-ui.card title="Resting heart rate" class="mt-6">
        <p class="health-section-desc">
            Your resting heart rate is measured while you sleep. A normal range is 40–100 bpm;
            athletes often sit between 40–60 bpm. A downward trend over weeks indicates improving cardiovascular fitness.
        </p>
        @if ($hrChart['values'])
            <canvas data-chart="line"
                    data-chart-data="{{ json_encode($hrChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No resting heart rate data yet." />
        @endif
    </x-ui.card>

    {{-- HRV trend --}}
    <x-ui.card title="Heart rate variability (HRV)" class="mt-6">
        <p class="health-section-desc">
            HRV measures the variation in time between heartbeats and is one of the best indicators of recovery and stress.
            A <strong style="color: var(--color-text-primary);">higher HRV</strong> means your nervous system is balanced and you are well-recovered.
            A sudden drop often signals fatigue, illness or high stress — useful to spot before you feel it.
            Apple Watch measures HRV during sleep using the SDNN method.
        </p>
        <p class="health-section-desc" style="margin-top: 0.5rem;">
            HRV is highly personal — comparing yourself to others matters far less than tracking your own trend.
            For men in their 20s–30s, a typical range is <strong style="color: var(--color-text-primary);">50–100 ms</strong>; above 70 ms is excellent.
            A drop of <strong style="color: var(--color-text-primary);">10–20% below your personal average</strong> is the meaningful signal.
            Common causes of a dip: alcohol (even one drink), poor sleep, illness, overtraining, or high stress.
            Consistent aerobic exercise, stable sleep and low stress gradually raise your baseline over weeks.
        </p>
        @if ($hrvChart['values'])
            <canvas data-chart="line"
                    data-chart-data="{{ json_encode($hrvChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No HRV data yet." />
        @endif
    </x-ui.card>

    {{-- Weight trend --}}
    <x-ui.card title="Weight" class="mt-6">
        <p class="health-section-desc">
            Body weight logged via Apple Health. Weigh yourself at the same time each day (e.g. morning after waking)
            for the most consistent trend. A single measurement means little — the trend over weeks is what matters.
        </p>
        @if ($weightChart['values'])
            <canvas data-chart="line"
                    data-chart-data="{{ json_encode($weightChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No weight data yet — log weight in the Apple Health app to see it here." />
        @endif
    </x-ui.card>

    {{-- Detail log --}}
    <x-ui.card title="Daily vitals log" class="mt-6">
        <p class="health-section-desc">
            All recorded vitals per day. Respiratory rate during sleep is a subtle but important metric —
            a sudden rise can be an early indicator of illness or respiratory issues.
        </p>
        @if ($history->isEmpty())
            <x-ui.empty-state message="No vitals data yet." />
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Resting HR</th>
                        <th>Avg HR</th>
                        <th>Min / Max HR</th>
                        <th>HRV</th>
                        <th>Respiratory rate</th>
                        <th>Weight</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history->reverse() as $entry)
                        <tr>
                            <td>{{ $entry->date->format('d M Y') }}</td>
                            <td>{{ $entry->resting_heart_rate ? $entry->resting_heart_rate . ' bpm' : '—' }}</td>
                            <td>{{ $entry->heart_rate_avg ? $entry->heart_rate_avg . ' bpm' : '—' }}</td>
                            <td>
                                @if ($entry->heart_rate_min && $entry->heart_rate_max)
                                    {{ $entry->heart_rate_min }} / {{ $entry->heart_rate_max }} bpm
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $entry->hrv ? round((float) $entry->hrv, 1) . ' ms' : '—' }}</td>
                            <td>{{ $entry->respiratory_rate ? round((float) $entry->respiratory_rate, 1) . ' /min' : '—' }}</td>
                            <td>{{ $entry->weight_kg ? $entry->weight_kg . ' kg' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>

    <x-layout.notification />

</x-layouts.app>
