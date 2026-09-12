<x-layouts.app title="Mobility">

    <x-layout.page-header title="Mobility" />

    <x-health.nav />

    <p class="health-page-intro">
        Gait and movement metrics measured passively by your iPhone while you walk.
        These numbers reveal how efficiently and symmetrically you move —
        subtle changes over months can reflect improvements in fitness, or early signs of fatigue or injury.
    </p>

    {{-- Stat cards --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Walking speed (avg)"
            :value="$avgWalkingSpeed ? round((float) $avgWalkingSpeed, 2) . ' km/h' : '—'"
        />
        <x-stats.stat-card
            label="Step length (avg)"
            :value="$avgStepLength ? round((float) $avgStepLength, 1) . ' cm' : '—'"
        />
        <x-stats.stat-card
            label="Asymmetry (avg)"
            :value="$avgAsymmetry ? round((float) $avgAsymmetry, 1) . '%' : '—'"
        />
        <x-stats.stat-card
            label="Double support (avg)"
            :value="$avgDoubleSupport ? round((float) $avgDoubleSupport, 1) . '%' : '—'"
        />
    </div>

    <div class="stats-row" style="margin-top: 0.75rem;">
        <x-stats.stat-card
            label="Daylight today"
            :value="$latest?->time_in_daylight_minutes !== null ? $latest->time_in_daylight_minutes . ' min' : '—'"
        />
        <x-stats.stat-card
            label="Avg daylight"
            :value="$avgDaylight ? (int) round($avgDaylight) . ' min' : '—'"
        />
        <x-stats.stat-card
            label="Walking speed (latest)"
            :value="$latest?->walking_speed_kmh ? round((float) $latest->walking_speed_kmh, 2) . ' km/h' : '—'"
        />
        <x-stats.stat-card
            label="Step length (latest)"
            :value="$latest?->walking_step_length_cm ? round((float) $latest->walking_step_length_cm, 1) . ' cm' : '—'"
        />
    </div>

    {{-- Walking speed chart --}}
    <x-ui.card title="Walking speed" class="mt-6">
        <p class="health-section-desc">
            Your average walking pace measured throughout the day.
            A typical adult walks between <strong style="color: var(--color-text-primary);">4–6 km/h</strong> — higher values suggest a naturally quicker, more energetic gait.
            A gradual decline without explanation is worth paying attention to.
        </p>
        @if ($speedChart['values'])
            <canvas data-chart="line"
                    data-chart-data="{{ json_encode($speedChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No walking speed data yet." />
        @endif
    </x-ui.card>

    {{-- Step length chart --}}
    <x-ui.card title="Step length" class="mt-6">
        <p class="health-section-desc">
            The distance covered per step. A longer stride generally indicates a more efficient gait.
            For reference, your Apple Watch has estimated your step length at roughly
            <strong style="color: var(--color-text-primary);">{{ $avgStepLength ? round((float) $avgStepLength, 0) . ' cm' : '—' }}</strong> on average.
            Fatigue, pain or muscle weakness can shorten stride length before you consciously notice it.
        </p>
        @if ($stepLengthChart['values'])
            <canvas data-chart="line"
                    data-chart-data="{{ json_encode($stepLengthChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No step length data yet." />
        @endif
    </x-ui.card>

    {{-- Time in daylight --}}
    <x-ui.card title="Time in daylight" class="mt-6">
        <p class="health-section-desc">
            Minutes spent outdoors in natural light, measured by the ambient light sensor on Apple Watch.
            Daylight exposure regulates your circadian rhythm, improves sleep quality and supports mood.
            The WHO recommends at least <strong style="color: var(--color-text-primary);">30 minutes</strong> of outdoor light daily;
            1–2 hours is associated with better sleep and wellbeing.
        </p>
        @if ($daylightChart['values'])
            <canvas data-chart="bar"
                    data-chart-data="{{ json_encode($daylightChart) }}"
                    style="max-height: 200px;"></canvas>
        @else
            <x-ui.empty-state message="No daylight data yet — requires Apple Watch Series 9 or newer." />
        @endif
    </x-ui.card>

    {{-- Detail log --}}
    <x-ui.card title="Daily mobility log" class="mt-6">
        <p class="health-section-desc">
            Walking asymmetry measures how evenly you distribute time between your left and right foot during each step.
            <strong style="color: var(--color-text-primary);">Below 5%</strong> is considered normal and symmetric;
            higher values can indicate compensation patterns due to fatigue or discomfort.
            Double support is the fraction of your step cycle where both feet are on the ground simultaneously —
            a higher percentage means a more cautious, slower gait.
        </p>
        @if ($history->isEmpty())
            <x-ui.empty-state message="No mobility data yet." />
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Walking speed</th>
                        <th>Step length</th>
                        <th>Asymmetry</th>
                        <th>Double support</th>
                        <th>Stair speed ↑</th>
                        <th>Stair speed ↓</th>
                        <th>Daylight</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history->reverse() as $entry)
                        <tr>
                            <td>{{ $entry->date->format('d M Y') }}</td>
                            <td>{{ $entry->walking_speed_kmh ? round((float) $entry->walking_speed_kmh, 2) . ' km/h' : '—' }}</td>
                            <td>{{ $entry->walking_step_length_cm ? round((float) $entry->walking_step_length_cm, 1) . ' cm' : '—' }}</td>
                            <td>
                                @if ($entry->walking_asymmetry_pct !== null)
                                    <span class="{{ (float) $entry->walking_asymmetry_pct > 5 ? 'text-yellow-400' : '' }}">
                                        {{ round((float) $entry->walking_asymmetry_pct, 1) }}%
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $entry->walking_double_support_pct !== null ? round((float) $entry->walking_double_support_pct, 1) . '%' : '—' }}</td>
                            <td>{{ $entry->stair_speed_up ? round((float) $entry->stair_speed_up, 2) . ' m/s' : '—' }}</td>
                            <td>{{ $entry->stair_speed_down ? round((float) $entry->stair_speed_down, 2) . ' m/s' : '—' }}</td>
                            <td>{{ $entry->time_in_daylight_minutes !== null ? $entry->time_in_daylight_minutes . ' min' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>

    <x-layout.notification />

</x-layouts.app>
