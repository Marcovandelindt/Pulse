<x-layouts.app title="Health Stats">

    <x-layout.page-header title="Health Stats">
        <x-slot:actions>
            <a href="{{ route('health.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
            <a href="{{ route('health.export') }}" class="btn btn--secondary btn--sm">Export CSV</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="health-stats-grid">

        {{-- Weekly comparison --}}
        <x-ui.card title="Weekly comparison">
            <div class="health-stat-comparison">
                <div class="health-stat-comparison__block">
                    <div class="health-stat-comparison__label">This week</div>
                    <div class="health-stat-comparison__value">{{ number_format((int) round($thisWeekAvg)) }}</div>
                    <div class="health-stat-comparison__sub">avg steps/day</div>
                </div>
                <div class="health-stat-comparison__divider">
                    <x-stats.trend-badge :trend="$weekChange" label="vs last week" />
                </div>
                <div class="health-stat-comparison__block health-stat-comparison__block--muted">
                    <div class="health-stat-comparison__label">Last week</div>
                    <div class="health-stat-comparison__value">{{ number_format((int) round($lastWeekAvg)) }}</div>
                    <div class="health-stat-comparison__sub">avg steps/day</div>
                </div>
            </div>
        </x-ui.card>

        {{-- Monthly comparison --}}
        <x-ui.card title="Monthly comparison">
            <div class="health-stat-comparison">
                <div class="health-stat-comparison__block">
                    <div class="health-stat-comparison__label">This month</div>
                    <div class="health-stat-comparison__value">{{ number_format((int) round($thisMonthAvg)) }}</div>
                    <div class="health-stat-comparison__sub">avg steps/day</div>
                </div>
                <div class="health-stat-comparison__divider">
                    <x-stats.trend-badge :trend="$monthChange" label="vs last month" />
                </div>
                <div class="health-stat-comparison__block health-stat-comparison__block--muted">
                    <div class="health-stat-comparison__label">Last month</div>
                    <div class="health-stat-comparison__value">{{ number_format((int) round($lastMonthAvg)) }}</div>
                    <div class="health-stat-comparison__sub">avg steps/day</div>
                </div>
            </div>
        </x-ui.card>

        {{-- Step goal achievement --}}
        <x-ui.card title="Step goal achievement">
            <div class="health-goal-rate">
                <div class="health-goal-rate__numbers">
                    <span class="health-goal-rate__percent">{{ $goalRate }}%</span>
                    <span class="health-goal-rate__sub">{{ $goalMetEntries }} of {{ $totalEntries }} days</span>
                </div>
                <div class="health-goal-rate__bar-track">
                    <div class="health-goal-rate__bar-fill" style="width: {{ $goalRate }}%"></div>
                </div>
                <div class="health-goal-rate__goal-label">Goal: {{ number_format($stepGoal) }} steps/day</div>
            </div>
        </x-ui.card>

        {{-- Streaks --}}
        <x-ui.card title="Streaks">
            <div class="health-streaks">
                <div class="health-streaks__block">
                    <div class="health-streaks__value">{{ $currentStreak }}</div>
                    <div class="health-streaks__label">Current streak</div>
                </div>
                <div class="health-streaks__divider"></div>
                <div class="health-streaks__block">
                    <div class="health-streaks__value">{{ $longestStreak }}</div>
                    <div class="health-streaks__label">Longest streak</div>
                </div>
            </div>
        </x-ui.card>

        {{-- Weekday patterns --}}
        <x-ui.card title="Weekday patterns" class="health-stats-grid__wide">
            @if ($weekdayPatterns->sum('count') > 0)
                @php $max = $weekdayPatterns->max('avg_steps') ?: 1; @endphp
                <div class="health-bar-chart">
                    @foreach ($weekdayPatterns as $day)
                        <div class="health-bar-chart__column">
                            <div class="health-bar-chart__bar-wrap">
                                <div class="health-bar-chart__bar {{ $day['avg_steps'] >= $stepGoal ? 'health-bar-chart__bar--goal' : '' }}"
                                     style="height: {{ round(($day['avg_steps'] / $max) * 100) }}%"
                                     title="{{ number_format($day['avg_steps']) }} avg steps"></div>
                            </div>
                            <div class="health-bar-chart__label">{{ $day['label'] }}</div>
                            <div class="health-bar-chart__value">{{ number_format($day['avg_steps']) }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="Not enough data yet" />
            @endif
        </x-ui.card>

        {{-- Monthly history --}}
        <x-ui.card title="Monthly history" class="health-stats-grid__wide">
            @if ($monthlyHistory->isNotEmpty())
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Entries</th>
                            <th>Total steps</th>
                            <th>Avg steps/day</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthlyHistory as $row)
                            <tr>
                                <td>{{ $row['month'] }}</td>
                                <td>{{ $row['entries'] }}</td>
                                <td>{{ $row['total_steps'] }}</td>
                                <td>{{ $row['avg_steps'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @else
                <x-ui.empty-state title="No data yet" />
            @endif
        </x-ui.card>

    </div>

</x-layouts.app>
