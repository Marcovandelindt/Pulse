<x-layouts.app title="Sleep">

    <x-layout.page-header title="Sleep" />

    <x-health.nav />

    <p class="health-page-intro">
        Nightly sleep sessions recorded by Apple Watch and Sleep Cycle.
        <strong style="color: var(--color-text-primary);">Deep</strong> sleep is the most physically restorative phase and supports tissue repair and immune function.
        <strong style="color: var(--color-text-primary);">REM</strong> sleep is where dreaming occurs and is critical for memory consolidation and mood regulation.
        <strong style="color: var(--color-text-primary);">Core</strong> (light) sleep fills the remainder of the night and acts as a transition between phases.
    </p>

    {{-- Stat cards --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Last night"
            :value="$lastSleep ? $lastSleep->formattedMinutes($lastSleep->total_sleep_minutes) : '—'"
        />
        <x-stats.stat-card
            label="Avg total sleep"
            :value="$avgTotal ? (new App\Models\HealthSleep)->formattedMinutes((int) round($avgTotal)) : '—'"
        />
        <x-stats.stat-card
            label="Avg deep sleep"
            :value="$avgDeep ? (new App\Models\HealthSleep)->formattedMinutes((int) round($avgDeep)) : '—'"
        />
        <x-stats.stat-card
            label="Avg REM"
            :value="$avgRem ? (new App\Models\HealthSleep)->formattedMinutes((int) round($avgRem)) : '—'"
        />
        <x-stats.stat-card
            label="Avg sleep score"
            :value="$avgScore !== null ? $avgScore . ' / 100' : '—'"
        />
    </div>

    {{-- Consistency stats --}}
    @if ($consistency['avgBedtime'])
        <div class="stats-row">
            <x-stats.stat-card
                label="Avg bedtime"
                :value="$consistency['avgBedtime']"
            />
            <x-stats.stat-card
                label="Avg wake time"
                :value="$consistency['avgWakeTime']"
            />
            <x-stats.stat-card
                label="Bedtime variability"
                :value="$consistency['bedVariability'] . ' min'"
            />
            <x-stats.stat-card
                label="Wake time variability"
                :value="$consistency['wakeVariability'] . ' min'"
            />
        </div>
    @endif

    {{-- Sleep legend --}}
    <div class="sleep-legend">
        <div class="sleep-legend__item">
            <span class="sleep-legend__dot sleep-legend__dot--deep"></span> Deep
        </div>
        <div class="sleep-legend__item">
            <span class="sleep-legend__dot sleep-legend__dot--rem"></span> REM
        </div>
        <div class="sleep-legend__item">
            <span class="sleep-legend__dot sleep-legend__dot--core"></span> Core
        </div>
        <div class="sleep-legend__item">
            <span class="sleep-legend__dot sleep-legend__dot--awake"></span> Awake
        </div>
    </div>

    {{-- 90-day trend chart --}}
    @if (count($trendData['labels']) > 1)
        <x-ui.card title="90-day sleep trend" class="mb-6">
            <p class="health-section-desc">Total sleep duration per night over the last 90 days, in hours.</p>
            <div class="sleep-trend-chart">
                <canvas data-chart="line" data-chart-data="{{ json_encode($trendData) }}"></canvas>
            </div>
        </x-ui.card>
    @endif

    {{-- Sleep debt tracker --}}
    @if ($debtData['weekNights'] > 0)
        @php
            $weekBalance    = $debtData['weekBalance'];
            $balanceLabel   = $weekBalance >= 0 ? 'ahead' : 'behind';
            $balanceMod     = $weekBalance >= 0 ? 'surplus' : 'deficit';
            $balanceAbs     = abs($weekBalance);
            $balanceH       = intdiv($balanceAbs, 60);
            $balanceM       = $balanceAbs % 60;
            $balanceFormatted = $balanceH > 0 ? "{$balanceH}h {$balanceM}m" : "{$balanceM}m";
            $helper         = new App\Models\HealthSleep;
        @endphp
        <x-ui.card title="Weekly sleep balance" class="mb-6">
            <p class="health-section-desc">
                Measured against a goal of {{ $helper->formattedMinutes($debtData['goalMinutes']) }} per night.
                Covers the last {{ $debtData['weekNights'] }} night(s) with recorded data.
            </p>
            <div class="sleep-debt">
                <div class="sleep-debt__balance">
                    <span class="sleep-debt__value sleep-debt__value--{{ $balanceMod }}">
                        {{ $weekBalance >= 0 ? '+' : '-' }}{{ $balanceFormatted }}
                    </span>
                    <span class="sleep-debt__label">this week vs. goal</span>
                </div>
                <div class="sleep-debt__all-time">
                    <div class="sleep-debt__all-time-block">
                        <div class="sleep-debt__all-time-value">{{ $helper->formattedMinutes(intdiv($debtData['allTimeDeficit'], 60) * 60 + $debtData['allTimeDeficit'] % 60) }}</div>
                        <div class="sleep-debt__all-time-label">All-time shortfall (vs. 8h)</div>
                    </div>
                    <div class="sleep-debt__all-time-block">
                        <div class="sleep-debt__all-time-value">{{ $helper->formattedMinutes(intdiv($debtData['allTimeSurplus'], 60) * 60 + $debtData['allTimeSurplus'] % 60) }}</div>
                        <div class="sleep-debt__all-time-label">All-time surplus (vs. 8h)</div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Sleep history --}}
    <x-ui.card title="Sleep history">
        <p class="health-section-desc">
            Each row is one sleep session. The coloured bar shows how your night was divided across phases —
            hover a segment to see the exact duration. The source indicates which app or device recorded the session.
        </p>
        @if ($records->isEmpty())
            <x-ui.empty-state message="No sleep data yet." />
        @else
            @foreach ($records as $record)
                @php
                    $base = ($record->deep_minutes ?? 0)
                          + ($record->rem_minutes  ?? 0)
                          + ($record->core_minutes ?? 0)
                          + ($record->awake_minutes ?? 0);
                @endphp
                <div class="sleep-record">
                    <div class="sleep-record__header">
                        <div class="sleep-record__date">
                            {{ $record->date->format('D d M Y') }}
                        </div>
                        <div class="sleep-record__times">
                            {{ $record->sleep_start->format('H:i') }}
                            &rarr;
                            {{ $record->sleep_end->format('H:i') }}
                        </div>
                        <div class="sleep-record__total">
                            {{ $record->formattedMinutes($record->total_sleep_minutes) }}
                        </div>
                        <span class="sleep-score sleep-score--{{ strtolower($record->sleepScoreLabel()) }}"
                              title="{{ $record->sleepScoreLabel() }}">
                            {{ $record->sleepScore() }}
                        </span>
                    </div>

                    @if ($base > 0)
                        <div class="sleep-record__bar">
                            @if ($record->deep_minutes)
                                <div class="sleep-record__bar-segment sleep-record__bar-segment--deep"
                                     style="width: {{ $record->phasePercent('deep_minutes', $base) }}%"
                                     title="Deep: {{ $record->formattedMinutes($record->deep_minutes) }}"></div>
                            @endif
                            @if ($record->rem_minutes)
                                <div class="sleep-record__bar-segment sleep-record__bar-segment--rem"
                                     style="width: {{ $record->phasePercent('rem_minutes', $base) }}%"
                                     title="REM: {{ $record->formattedMinutes($record->rem_minutes) }}"></div>
                            @endif
                            @if ($record->core_minutes)
                                <div class="sleep-record__bar-segment sleep-record__bar-segment--core"
                                     style="width: {{ $record->phasePercent('core_minutes', $base) }}%"
                                     title="Core: {{ $record->formattedMinutes($record->core_minutes) }}"></div>
                            @endif
                            @if ($record->awake_minutes)
                                <div class="sleep-record__bar-segment sleep-record__bar-segment--awake"
                                     style="width: {{ $record->phasePercent('awake_minutes', $base) }}%"
                                     title="Awake: {{ $record->formattedMinutes($record->awake_minutes) }}"></div>
                            @endif
                        </div>

                        <div class="sleep-record__phases">
                            @if ($record->deep_minutes)
                                <span class="sleep-record__phase">
                                    <span class="sleep-record__phase-dot sleep-record__phase-dot--deep"></span>
                                    {{ $record->formattedMinutes($record->deep_minutes) }} deep
                                </span>
                            @endif
                            @if ($record->rem_minutes)
                                <span class="sleep-record__phase">
                                    <span class="sleep-record__phase-dot sleep-record__phase-dot--rem"></span>
                                    {{ $record->formattedMinutes($record->rem_minutes) }} REM
                                </span>
                            @endif
                            @if ($record->core_minutes)
                                <span class="sleep-record__phase">
                                    <span class="sleep-record__phase-dot sleep-record__phase-dot--core"></span>
                                    {{ $record->formattedMinutes($record->core_minutes) }} core
                                </span>
                            @endif
                            @if ($record->awake_minutes)
                                <span class="sleep-record__phase">
                                    <span class="sleep-record__phase-dot sleep-record__phase-dot--awake"></span>
                                    {{ $record->formattedMinutes($record->awake_minutes) }} awake
                                </span>
                            @endif
                            @if ($record->source)
                                <span class="sleep-record__source">{{ $record->source }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </x-ui.card>

    <x-layout.notification />

</x-layouts.app>
