<x-layouts.app title="Activity">

    <x-layout.page-header title="Activity" />

    <x-health.nav />

    <p class="health-page-intro">
        Daily energy expenditure and physical activity from Apple Watch.
        <strong style="color: var(--color-text-primary);">Active calories</strong> are burned through movement and exercise — on top of the basal calories your body burns at rest simply to keep running.
        Apple Watch tracks three rings: <strong style="color: var(--color-text-primary);">Move</strong> (active calories),
        <strong style="color: var(--color-text-primary);">Exercise</strong> (minutes of brisk activity, goal: 30 min), and
        <strong style="color: var(--color-text-primary);">Stand</strong> (hours with at least one standing minute, goal: 12h).
    </p>

    {{-- Stat cards --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Avg active calories (30d)"
            :value="$avgActiveCalories ? number_format($avgActiveCalories) . ' kcal' : '—'"
        />
        <x-stats.stat-card
            label="Total active calories (30d)"
            :value="$totalActiveCalories ? number_format((int) $totalActiveCalories) . ' kcal' : '—'"
        />
        <x-stats.stat-card
            label="Exercise minutes"
            :value="$latestAppleWatch?->exercise_minutes !== null
                ? $latestAppleWatch->exercise_minutes . ' min'
                : '—'"
        />
        <x-stats.stat-card
            label="Stand hours"
            :value="$latestAppleWatch?->stand_hours !== null
                ? $latestAppleWatch->stand_hours . ' / 12h'
                : '—'"
        />
    </div>

    {{-- Active calories chart --}}
    <x-ui.card title="Active calories — last 30 days" class="mt-6">
        @if ($calorieChart['values'])
            <canvas data-chart="bar"
                    data-chart-data="{{ json_encode($calorieChart) }}"
                    style="max-height: 220px;"></canvas>
        @else
            <x-ui.empty-state message="No calorie data yet." />
        @endif
    </x-ui.card>

    {{-- Apple Watch activity log --}}
    <x-ui.card title="Apple Watch activity" class="mt-6">
        <p class="health-section-desc">
            Detailed daily breakdown of energy and activity ring data. Total calories = active + basal.
            Stand hours are highlighted green when the daily goal of 12 hours is reached.
            This table grows automatically each night when your Watch syncs via iCloud.
        </p>
        @if ($appleWatchHistory->isEmpty())
            <x-ui.empty-state message="No Apple Watch activity data yet — this grows as your Watch syncs nightly." />
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Active cal</th>
                        <th>Basal cal</th>
                        <th>Total cal</th>
                        <th>Exercise</th>
                        <th>Stand</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appleWatchHistory as $entry)
                        <tr>
                            <td>{{ $entry->date->format('d M Y') }}</td>
                            <td>{{ $entry->active_calories ? number_format($entry->active_calories) . ' kcal' : '—' }}</td>
                            <td>{{ $entry->basal_calories ? number_format($entry->basal_calories) . ' kcal' : '—' }}</td>
                            <td>
                                @if ($entry->active_calories && $entry->basal_calories)
                                    {{ number_format($entry->active_calories + $entry->basal_calories) }} kcal
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $entry->exercise_minutes !== null ? $entry->exercise_minutes . ' min' : '—' }}</td>
                            <td>
                                @if ($entry->stand_hours !== null)
                                    <span class="{{ $entry->stand_hours >= 12 ? 'text-green-400' : '' }}">
                                        {{ $entry->stand_hours }} / 12h
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>

    <x-layout.notification />

</x-layouts.app>
