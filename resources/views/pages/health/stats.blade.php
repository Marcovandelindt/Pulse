<x-layouts.app title="Health Stats">

    <x-layout.page-header title="Health Stats">
        <x-slot:actions>
            <a href="{{ route('health.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
            <a href="{{ route('health.export') }}" class="btn btn--secondary btn--sm">Export CSV</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- All-time totals --}}
    <div class="stats-row mb-6">
        <x-stats.stat-card label="Total steps" :value="number_format($allTimeSteps)" icon="heart" />
        <x-stats.stat-card label="Total distance" :value="number_format($allTimeKm, 1) . ' km'" icon="map-pin" />
        <x-stats.stat-card label="Distance this year" :value="number_format($thisYearKm, 1) . ' km'" icon="calendar" />
        <x-stats.stat-card label="Days logged" :value="number_format($totalEntries)" icon="check-circle" />
    </div>

    {{-- Distance comparisons --}}
    <x-ui.card title="Distance milestones" class="mb-6">
        <div class="health-distance-refs">
            @foreach ($distanceComparisons as $ref)
                <div class="health-distance-ref">
                    <div class="health-distance-ref__header">
                        <span class="health-distance-ref__label">{{ $ref['label'] }}</span>
                        <span class="health-distance-ref__meta">
                            @if ($ref['times'] >= 1)
                                <strong>{{ $ref['times'] }}×</strong> completed
                            @else
                                {{ $ref['percent'] }}% of {{ number_format($ref['km']) }} km
                            @endif
                        </span>
                    </div>
                    <div class="health-distance-ref__track">
                        <div class="health-distance-ref__fill {{ $ref['times'] >= 1 ? 'health-distance-ref__fill--done' : '' }}"
                             style="width: {{ $ref['percent'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Personal records --}}
    <x-ui.card title="Personal records" class="mb-6">
        <div class="health-records">
            <div class="health-record">
                <div class="health-record__icon">🏅</div>
                <div class="health-record__body">
                    <div class="health-record__value">
                        {{ $personalRecords['bestDaySteps'] ? number_format($personalRecords['bestDaySteps']) : '—' }}
                    </div>
                    <div class="health-record__label">Best day ever</div>
                    @if ($personalRecords['bestDayDate'])
                        <div class="health-record__sub">{{ $personalRecords['bestDayDate'] }}</div>
                    @endif
                </div>
            </div>

            <div class="health-record">
                <div class="health-record__icon">📅</div>
                <div class="health-record__body">
                    <div class="health-record__value">
                        {{ $personalRecords['bestWeekSteps'] ? number_format($personalRecords['bestWeekSteps']) : '—' }}
                    </div>
                    <div class="health-record__label">Best week ever</div>
                    @if ($personalRecords['bestWeekStart'])
                        <div class="health-record__sub">Week of {{ $personalRecords['bestWeekStart'] }}</div>
                    @endif
                </div>
            </div>

            <div class="health-record">
                <div class="health-record__icon">🗓️</div>
                <div class="health-record__body">
                    <div class="health-record__value">
                        {{ $personalRecords['bestMonthSteps'] ? number_format($personalRecords['bestMonthSteps']) : '—' }}
                    </div>
                    <div class="health-record__label">Best month ever</div>
                    @if ($personalRecords['bestMonthLabel'])
                        <div class="health-record__sub">{{ $personalRecords['bestMonthLabel'] }}</div>
                    @endif
                </div>
            </div>
        </div>
    </x-ui.card>

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

            <div class="health-goal-tiers">
                <div class="health-goal-tier health-goal-tier--good">
                    <div class="health-goal-tier__value">{{ number_format($goalPerformance['above150']) }}</div>
                    <div class="health-goal-tier__label">Days above 150%</div>
                </div>
                <div class="health-goal-tier health-goal-tier--great">
                    <div class="health-goal-tier__value">{{ number_format($goalPerformance['above200']) }}</div>
                    <div class="health-goal-tier__label">Days above 200%</div>
                </div>
                <div class="health-goal-tier health-goal-tier--low">
                    <div class="health-goal-tier__value">{{ number_format($goalPerformance['below50']) }}</div>
                    <div class="health-goal-tier__label">Days below 50%</div>
                </div>
            </div>

            @if ($goalPerformance['avgStepsMet'] || $goalPerformance['avgStepsMissed'])
                <div class="health-goal-avg-compare">
                    @if ($goalPerformance['avgStepsMet'])
                        <div class="health-goal-avg-compare__block health-goal-avg-compare__block--met">
                            <div class="health-goal-avg-compare__value">{{ number_format($goalPerformance['avgStepsMet']) }}</div>
                            <div class="health-goal-avg-compare__label">avg on goal days</div>
                        </div>
                    @endif
                    @if ($goalPerformance['avgStepsMissed'])
                        <div class="health-goal-avg-compare__block health-goal-avg-compare__block--missed">
                            <div class="health-goal-avg-compare__value">{{ number_format($goalPerformance['avgStepsMissed']) }}</div>
                            <div class="health-goal-avg-compare__label">avg on missed days</div>
                        </div>
                    @endif
                </div>
            @endif
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

        {{-- Steps distribution --}}
        <x-ui.card title="Steps distribution" class="health-stats-grid__wide">
            @if (array_sum(array_column($stepsDistribution, 'count')) > 0)
                @php $maxCount = max(array_column($stepsDistribution, 'count')) ?: 1; @endphp
                <div class="health-bar-chart">
                    @foreach ($stepsDistribution as $bucket)
                        <div class="health-bar-chart__column">
                            <div class="health-bar-chart__bar-wrap">
                                <div class="health-bar-chart__bar
                                    {{ $bucket['isGoalZone'] ? 'health-bar-chart__bar--goal-zone' : '' }}"
                                     style="height: {{ round(($bucket['count'] / $maxCount) * 100) }}%"
                                     title="{{ $bucket['count'] }} days ({{ $bucket['percent'] }}%)"></div>
                            </div>
                            <div class="health-bar-chart__label">
                                {{ $bucket['label'] }}
                                @if ($bucket['isGoalZone'])
                                    <span class="health-bar-chart__goal-marker">goal</span>
                                @endif
                            </div>
                            <div class="health-bar-chart__value">{{ $bucket['count'] }}d · {{ $bucket['percent'] }}%</div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="Not enough data yet" />
            @endif
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

        {{-- Goal rate by month --}}
        <x-ui.card title="Goal achievement by month" class="health-stats-grid__wide">
            @if ($goalPerformance['goalByMonth']->isNotEmpty())
                @php $maxRate = $goalPerformance['goalByMonth']->max('rate') ?: 1; @endphp
                <div class="health-bar-chart health-bar-chart--goal-month">
                    @foreach ($goalPerformance['goalByMonth'] as $row)
                        <div class="health-bar-chart__column">
                            <div class="health-bar-chart__bar-wrap">
                                <div class="health-bar-chart__bar {{ $row['rate'] >= 80 ? 'health-bar-chart__bar--goal' : '' }}"
                                     style="height: {{ round(($row['rate'] / $maxRate) * 100) }}%"
                                     title="{{ $row['rate'] }}% — {{ $row['met'] }}/{{ $row['total'] }} days"></div>
                            </div>
                            <div class="health-bar-chart__label">{{ $row['month'] }}</div>
                            <div class="health-bar-chart__value">{{ $row['rate'] }}%</div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="Not enough data yet" />
            @endif
        </x-ui.card>

        {{-- Goal history --}}
        <x-ui.card title="Goal history" class="health-stats-grid__wide">
            @if ($goalHistory->isNotEmpty())
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Effective from</th>
                            <th>Daily step goal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($goalHistory as $goal)
                            <tr>
                                <td>{{ $goal->effective_from->format('d M Y') }}</td>
                                <td>{{ number_format($goal->steps) }} steps</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @else
                <x-ui.empty-state title="No goal history yet" />
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
