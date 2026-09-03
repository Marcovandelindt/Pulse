<x-layouts.app title="Weekly Report">

    <x-layout.page-header
        title="Weekly Report"
        :subtitle="$report['week_start']->format('M j') . ' – ' . $report['week_end']->format('M j, Y')"
    >
        <x-slot:actions>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ $prevWeekUrl }}" class="btn btn--secondary btn--sm">
                    &larr; Previous
                </a>
                @if ($nextWeekUrl)
                    <a href="{{ $nextWeekUrl }}" class="btn btn--secondary btn--sm">
                        Next &rarr;
                    </a>
                @endif
            </div>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="week-report">

        {{-- Steps --}}
        @if ($report['steps']['days'] > 0)
            <div class="week-report__section">
                <h2 class="week-report__section-title">Steps</h2>
                <div class="week-report__grid">
                    <x-stats.stat-card
                        label="Total steps"
                        :value="number_format($report['steps']['total'])"
                        :subtitle="$report['steps']['prev_total'] > 0 ? 'Last week: ' . number_format($report['steps']['prev_total']) : null"
                        icon="heart"
                        :trend="$report['steps']['vs_prev']"
                        trend-label="vs last week"
                    />
                    <x-stats.stat-card
                        label="Daily average"
                        :value="number_format($report['steps']['avg'])"
                        :subtitle="$report['steps']['days'] . ' days logged'"
                    />
                    @if ($report['steps']['best'])
                        <x-stats.stat-card
                            label="Best day"
                            :value="number_format($report['steps']['best']['value'])"
                            :subtitle="$report['steps']['best']['date']->format('l')"
                        />
                    @endif
                </div>
            </div>
        @endif

        {{-- Gaming --}}
        @if ($report['gaming']['sessions'] > 0)
            <div class="week-report__section">
                <h2 class="week-report__section-title">Gaming</h2>
                <div class="week-report__grid">
                    <x-stats.stat-card
                        label="Time played"
                        :value="$report['gaming']['total_minutes'] >= 60
                            ? intdiv($report['gaming']['total_minutes'], 60) . 'h ' . ($report['gaming']['total_minutes'] % 60) . 'm'
                            : $report['gaming']['total_minutes'] . 'm'"
                        icon="gamepad"
                        :trend="$report['gaming']['vs_prev']"
                        trend-label="vs last week"
                    />
                    <x-stats.stat-card
                        label="Sessions"
                        :value="$report['gaming']['sessions']"
                    />
                    @if ($report['gaming']['top_game'])
                        @php
                            $gm = $report['gaming']['top_game_minutes'];
                            $gameTime = $gm >= 60
                                ? intdiv($gm, 60) . 'h ' . ($gm % 60) . 'm'
                                : $gm . 'm';
                        @endphp
                        <x-stats.stat-card
                            label="Most played"
                            :value="$report['gaming']['top_game']"
                            :subtitle="$gameTime"
                        />
                    @endif
                </div>
            </div>
        @endif

        {{-- Music --}}
        @if ($report['music']['total'] > 0)
            <div class="week-report__section">
                <h2 class="week-report__section-title">Music</h2>
                <div class="week-report__grid">
                    <x-stats.stat-card
                        label="Tracks played"
                        :value="number_format($report['music']['total'])"
                        icon="musical-note"
                        :trend="$report['music']['vs_prev']"
                        trend-label="vs last week"
                    />
                    <x-stats.stat-card
                        label="Unique tracks"
                        :value="number_format($report['music']['unique_tracks'])"
                    />
                    @if ($report['music']['top_artist'])
                        <x-stats.stat-card
                            label="Top artist"
                            :value="$report['music']['top_artist']"
                        />
                    @endif
                </div>
            </div>
        @endif

        {{-- Media --}}
        @if ($report['media']['episodes'] + $report['media']['films'] > 0)
            <div class="week-report__section">
                <h2 class="week-report__section-title">Media</h2>
                <div class="week-report__grid">
                    <x-stats.stat-card
                        label="Episodes watched"
                        :value="$report['media']['episodes']"
                        icon="film"
                        :trend="$report['media']['vs_prev']"
                        trend-label="vs last week"
                    />
                    <x-stats.stat-card
                        label="Films watched"
                        :value="$report['media']['films']"
                    />
                </div>
            </div>
        @endif

        {{-- Records broken --}}
        @if (count($report['records']) > 0)
            <div class="week-report__section">
                <h2 class="week-report__section-title">Records broken this week 🏆</h2>
                <div class="week-report__grid">
                    @foreach ($report['records'] as $record)
                        <x-stats.stat-card
                            :label="$record['label']"
                            :value="$record['value']"
                            :subtitle="$record['prev']"
                            icon="trophy"
                        />
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty state --}}
        @if ($report['steps']['days'] === 0 && $report['gaming']['sessions'] === 0 && $report['music']['total'] === 0 && $report['media']['episodes'] + $report['media']['films'] === 0)
            <div class="week-report__empty">
                No data recorded for this week.
            </div>
        @endif

    </div>

</x-layouts.app>
