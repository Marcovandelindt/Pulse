<x-layouts.app title="PlayStation Stats">

    <x-layout.page-header title="PlayStation Stats">
        <x-slot:actions>
            <a href="{{ route('playstation.wrapped') }}" class="btn btn--secondary btn--sm">Wrapped</a>
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Year in review --}}
    @if ($yearInReview['hasData'])
        <x-ui.card class="psn-year-review mb-6">
            <div class="psn-year-review__header">
                <span class="psn-year-review__year">{{ $yearInReview['year'] }}</span>
                <span class="psn-year-review__title">Year in review</span>
                @if ($yearInReview['vsLastYear'] !== null)
                    <span class="psn-year-review__vs {{ $yearInReview['vsLastYear'] >= 0 ? 'psn-year-review__vs--up' : 'psn-year-review__vs--down' }}">
                        {{ $yearInReview['vsLastYear'] >= 0 ? '+' : '' }}{{ $yearInReview['vsLastYear'] }}% vs {{ $yearInReview['year'] - 1 }}
                    </span>
                @endif
            </div>

            <div class="psn-year-review__grid">
                <div class="psn-year-review__block">
                    <div class="psn-year-review__value">{{ number_format($yearInReview['totalHours'], 1) }}h</div>
                    <div class="psn-year-review__label">Hours played</div>
                </div>
                <div class="psn-year-review__block">
                    <div class="psn-year-review__value">{{ number_format($yearInReview['totalSessions']) }}</div>
                    <div class="psn-year-review__label">Sessions</div>
                </div>
                <div class="psn-year-review__block">
                    <div class="psn-year-review__value">{{ number_format($yearInReview['uniqueGames']) }}</div>
                    <div class="psn-year-review__label">Games played</div>
                </div>
                @if ($yearInReview['bestMonth'])
                    <div class="psn-year-review__block">
                        <div class="psn-year-review__value psn-year-review__value--sm">{{ $yearInReview['bestMonth'] }}</div>
                        <div class="psn-year-review__label">Best month · {{ $yearInReview['bestMonthHours'] }}h</div>
                    </div>
                @endif
                <div class="psn-year-review__block">
                    <div class="psn-year-review__value">{{ number_format($yearInReview['newGames']) }}</div>
                    <div class="psn-year-review__label">New games started</div>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- All-time stat cards --}}
    <div class="stats-row mb-6">
        <x-stats.stat-card label="Total hours" :value="number_format($totalHours, 1) . 'h'" icon="clock" />
        <x-stats.stat-card label="Total sessions" :value="number_format($totalSessions)" icon="play" />
        <x-stats.stat-card label="Games in library" :value="number_format($totalGames)" icon="collection" />
        <x-stats.stat-card label="Avg session" :value="$avgSessionMinutes . 'm'" icon="chart-bar" />
    </div>

    {{-- Personal records --}}
    <x-ui.card title="Personal records" class="mb-6">
        <div class="psn-records">
            <div class="psn-record">
                <div class="psn-record__icon">⏱️</div>
                <div class="psn-record__body">
                    <div class="psn-record__value">{{ $personalRecords['longestSessionFormatted'] ?? '—' }}</div>
                    <div class="psn-record__label">Longest session</div>
                    @if ($personalRecords['longestSessionGame'])
                        <div class="psn-record__sub">{{ $personalRecords['longestSessionGame'] }}</div>
                    @endif
                    @if ($personalRecords['longestSessionDate'])
                        <div class="psn-record__sub">{{ $personalRecords['longestSessionDate'] }}</div>
                    @endif
                </div>
            </div>

            <div class="psn-record">
                <div class="psn-record__icon">🎮</div>
                <div class="psn-record__body">
                    <div class="psn-record__value">{{ $personalRecords['mostPlayedFormatted'] ?? '—' }}</div>
                    <div class="psn-record__label">Most played game</div>
                    @if ($personalRecords['mostPlayedGameName'])
                        <div class="psn-record__sub">
                            @if ($personalRecords['mostPlayedGameId'])
                                <a href="{{ route('playstation.show', $personalRecords['mostPlayedGameId']) }}" class="psn-record__link">
                                    {{ $personalRecords['mostPlayedGameName'] }}
                                </a>
                            @else
                                {{ $personalRecords['mostPlayedGameName'] }}
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="psn-record">
                <div class="psn-record__icon">🔥</div>
                <div class="psn-record__body">
                    <div class="psn-record__value">{{ $personalRecords['busiestDayFormatted'] ?? '—' }}</div>
                    <div class="psn-record__label">Most played in one day</div>
                    @if ($personalRecords['busiestDayDate'])
                        <div class="psn-record__sub">{{ $personalRecords['busiestDayDate'] }} · {{ $personalRecords['busiestDaySessions'] }} sessions</div>
                    @endif
                </div>
            </div>

            <div class="psn-record">
                <div class="psn-record__icon">📅</div>
                <div class="psn-record__body">
                    <div class="psn-record__value">{{ $personalRecords['mostSessionsDayCount'] ?? '—' }}</div>
                    <div class="psn-record__label">Most sessions in one day</div>
                    @if ($personalRecords['mostSessionsDayDate'])
                        <div class="psn-record__sub">{{ $personalRecords['mostSessionsDayDate'] }} · {{ $personalRecords['mostSessionsDayFormatted'] }}</div>
                    @endif
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="psn-stats-grid">

        {{-- Platform breakdown --}}
        <x-ui.card title="Hours by platform">
            @if ($platformHours->isNotEmpty())
                <div class="psn-platform-bars">
                    @foreach ($platformHours as $row)
                        <div class="psn-platform-bar">
                            <div class="psn-platform-bar__header">
                                <span class="psn-platform-bar__label">{{ $row['platform'] }}</span>
                                <span class="psn-platform-bar__meta">{{ $row['hours'] }}h · {{ $row['game_count'] }} games</span>
                            </div>
                            <div class="psn-platform-bar__track">
                                <div class="psn-platform-bar__fill" style="width: {{ $row['percent'] }}%; background: {{ $row['color'] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="No sessions yet" />
            @endif
        </x-ui.card>

        {{-- Trophy stats --}}
        <x-ui.card title="Trophies">

            <div class="psn-trophy-platinum">
                <span class="psn-trophy-platinum__icon">🏆</span>
                <div>
                    <div class="psn-trophy-platinum__value">{{ number_format($trophyStats['platinum']) }}</div>
                    <div class="psn-trophy-platinum__label">Platinums earned</div>
                </div>
            </div>

            <div class="psn-trophy-types">
                <div class="psn-trophy-type" style="--trophy-color: #c9a227;">
                    <div class="psn-trophy-type__icon">🥇</div>
                    <div class="psn-trophy-type__value">{{ number_format($trophyStats['gold']) }}</div>
                    <div class="psn-trophy-type__label">Gold</div>
                </div>
                <div class="psn-trophy-type" style="--trophy-color: #9ea3a8;">
                    <div class="psn-trophy-type__icon">🥈</div>
                    <div class="psn-trophy-type__value">{{ number_format($trophyStats['silver']) }}</div>
                    <div class="psn-trophy-type__label">Silver</div>
                </div>
                <div class="psn-trophy-type" style="--trophy-color: #b36a2a;">
                    <div class="psn-trophy-type__icon">🥉</div>
                    <div class="psn-trophy-type__value">{{ number_format($trophyStats['bronze']) }}</div>
                    <div class="psn-trophy-type__label">Bronze</div>
                </div>
            </div>

            <div class="psn-trophy-total">
                {{ number_format($trophyStats['totalEarned']) }} trophies earned in total
            </div>
        </x-ui.card>

        {{-- Genre breakdown --}}
        @if ($genreBreakdown->isNotEmpty())
            <x-ui.card title="Hours by genre" class="psn-stats-grid__wide">
                <div class="psn-platform-bars">
                    @foreach ($genreBreakdown as $row)
                        <div class="psn-platform-bar">
                            <div class="psn-platform-bar__header">
                                <span class="psn-platform-bar__label">{{ $row['name'] }}</span>
                                <span class="psn-platform-bar__meta">{{ $row['hours'] }}h · {{ $row['game_count'] }} {{ $row['game_count'] === 1 ? 'game' : 'games' }}</span>
                            </div>
                            <div class="psn-platform-bar__track">
                                <div class="psn-platform-bar__fill" style="width: {{ $row['pct'] }}%; background: var(--color-brand);"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        {{-- Weekday patterns --}}
        <x-ui.card title="Active day of week" class="psn-stats-grid__wide">
            @if ($weekdayPatterns->sum('count') > 0)
                @php $maxAvg = $weekdayPatterns->max('avg_minutes') ?: 1; @endphp
                <div class="health-bar-chart">
                    @foreach ($weekdayPatterns as $day)
                        <div class="health-bar-chart__column">
                            <div class="health-bar-chart__bar-wrap">
                                <div class="health-bar-chart__bar"
                                     style="height: {{ round(($day['avg_minutes'] / $maxAvg) * 100) }}%"
                                     title="{{ $day['avg_minutes'] }}m avg · {{ $day['count'] }} sessions"></div>
                            </div>
                            <div class="health-bar-chart__label">{{ $day['label'] }}</div>
                            <div class="health-bar-chart__value">{{ $day['avg_minutes'] }}m</div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="Not enough data yet" />
            @endif
        </x-ui.card>

        {{-- Hourly patterns --}}
        <x-ui.card title="What time do you play?" class="psn-stats-grid__wide">
            @if ($hourlyPatterns->sum('count') > 0)
                @php $maxCount = $hourlyPatterns->max('count') ?: 1; @endphp
                <div class="psn-hourly-chart">
                    @foreach ($hourlyPatterns as $hour)
                        <div class="psn-hourly-chart__col">
                            <div class="psn-hourly-chart__bar-wrap">
                                <div class="psn-hourly-chart__bar"
                                     style="height: {{ round(($hour['count'] / $maxCount) * 100) }}%"
                                     title="{{ $hour['count'] }} sessions at {{ $hour['label'] }}"></div>
                            </div>
                            <div class="psn-hourly-chart__label">
                                {{ in_array($hour['hour'], [0, 6, 12, 18]) ? $hour['label'] : '' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="Not enough data yet" />
            @endif
        </x-ui.card>

        {{-- Monthly trend --}}
        <x-ui.card title="Monthly playtime (last 12 months)" class="psn-stats-grid__wide">
            @if ($monthlyTrend->isNotEmpty())
                @php $maxHours = $monthlyTrend->max('hours') ?: 1; @endphp
                <div class="health-bar-chart">
                    @foreach ($monthlyTrend as $row)
                        <div class="health-bar-chart__column">
                            <div class="health-bar-chart__bar-wrap">
                                <div class="health-bar-chart__bar"
                                     style="height: {{ round(($row['hours'] / $maxHours) * 100) }}%"
                                     title="{{ $row['hours'] }}h · {{ $row['session_count'] }} sessions in {{ $row['month'] }}"></div>
                            </div>
                            <div class="health-bar-chart__label">{{ substr($row['month'], 0, 3) }}</div>
                            <div class="health-bar-chart__value">{{ $row['hours'] }}h</div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="Not enough data yet" />
            @endif
        </x-ui.card>

        {{-- Gaming velocity (all-time) --}}
        @if ($gamingVelocity->isNotEmpty())
            <x-ui.card title="Gaming velocity — all time" class="psn-stats-grid__wide">
                @php
                    $maxVelocityHours = $gamingVelocity->max('hours') ?: 1;
                    $avgVelocityHours = round($gamingVelocity->avg('hours'), 1);
                    $aboveAvgCount    = $gamingVelocity->where('above_avg', true)->count();
                    $prevYear         = null;
                @endphp
                <div class="psn-velocity-chart">
                    @foreach ($gamingVelocity as $row)
                        @if ($row['year'] !== $prevYear)
                            @php $prevYear = $row['year']; @endphp
                            <div class="psn-velocity-chart__col psn-velocity-chart__col--year">
                                <div class="psn-velocity-chart__bar-wrap"></div>
                                <div class="psn-velocity-chart__label psn-velocity-chart__label--year">{{ $row['year'] }}</div>
                            </div>
                        @endif
                        <div class="psn-velocity-chart__col">
                            <div class="psn-velocity-chart__bar-wrap">
                                <div class="psn-velocity-chart__bar {{ $row['above_avg'] ? 'psn-velocity-chart__bar--above' : '' }}"
                                     style="height: {{ round(($row['hours'] / $maxVelocityHours) * 100) }}%"
                                     title="{{ $row['hours'] }}h — {{ $row['month'] }}"></div>
                            </div>
                            <div class="psn-velocity-chart__label">{{ substr($row['month'], 0, 1) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="psn-velocity-footer">
                    Avg {{ $avgVelocityHours }}h/month · <strong>{{ $aboveAvgCount }}</strong> of {{ $gamingVelocity->count() }} months above average
                </div>
            </x-ui.card>
        @endif

        {{-- Session length distribution + Completion funnel --}}
        <x-ui.card title="Session length distribution">
            @if ($sessionLengthDist['total'] > 0)
                <div class="psn-histogram">
                    @foreach ($sessionLengthDist['buckets'] as $bucket)
                        <div class="psn-histogram__row">
                            <div class="psn-histogram__label">{{ $bucket['label'] }}</div>
                            <div class="psn-histogram__bar-wrap">
                                <div class="psn-histogram__bar" style="width: {{ $bucket['bar_pct'] }}%; background: {{ $bucket['color'] }};"></div>
                            </div>
                            <div class="psn-histogram__count">{{ $bucket['count'] }} <span class="psn-histogram__pct">({{ $bucket['pct'] }}%)</span></div>
                        </div>
                    @endforeach
                </div>
                <div class="psn-histogram__total">{{ number_format($sessionLengthDist['total']) }} sessions total</div>
            @else
                <x-ui.empty-state title="No sessions yet" />
            @endif
        </x-ui.card>

        <x-ui.card title="Completion funnel">
            @if ($completionFunnel['total'] > 0)
                <div class="psn-funnel">
                    @foreach ($completionFunnel['tiers'] as $tier)
                        <div class="psn-funnel__tier">
                            <div class="psn-funnel__header">
                                <span class="psn-funnel__icon">{{ $tier['icon'] }}</span>
                                <span class="psn-funnel__label">{{ $tier['label'] }}</span>
                                <span class="psn-funnel__count">{{ $tier['count'] }}</span>
                                <span class="psn-funnel__pct">{{ $tier['pct'] }}%</span>
                            </div>
                            <div class="psn-funnel__bar-wrap">
                                <div class="psn-funnel__bar" style="width: {{ $tier['pct'] }}%; background: {{ $tier['color'] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="psn-funnel__footer">{{ number_format($completionFunnel['total']) }} games in library</div>
            @else
                <x-ui.empty-state title="No games yet" />
            @endif
        </x-ui.card>

        {{-- Library / backlog --}}
        <x-ui.card title="Library breakdown">
            @if ($libraryStats['statusTotal'] > 0)
                <div class="psn-backlog-list">
                    @foreach ($libraryStats['statuses'] as $status)
                        @if ($status['count'] > 0)
                            <div class="psn-backlog-item">
                                <div class="psn-backlog-item__header">
                                    <span class="psn-backlog-item__icon">{{ $status['icon'] }}</span>
                                    <span class="psn-backlog-item__label">{{ $status['label'] }}</span>
                                    <span class="psn-backlog-item__count">{{ $status['count'] }}</span>
                                    <span class="psn-backlog-item__pct">
                                        {{ round($status['count'] / $libraryStats['statusTotal'] * 100) }}%
                                    </span>
                                </div>
                                <div class="psn-backlog-item__track">
                                    <div class="psn-backlog-item__fill"
                                         style="width: {{ round($status['count'] / $libraryStats['statusTotal'] * 100) }}%; background: {{ $status['color'] }};"></div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="psn-backlog-footer">
                    <span>Avg completion</span>
                    <strong>{{ $libraryStats['avgCompletion'] }}%</strong>
                </div>
            @else
                <x-ui.empty-state title="No games yet" />
            @endif

            @if ($libraryStats['platformCounts']->isNotEmpty())
                <div class="psn-platform-counts">
                    @foreach ($libraryStats['platformCounts'] as $row)
                        <div class="psn-platform-count">
                            <span class="psn-platform-count__label">{{ $row['platform'] }}</span>
                            <span class="psn-platform-count__value">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Consistency --}}
        <x-ui.card title="Consistency">
            <div class="psn-consistency">
                <div class="psn-consistency__block">
                    <div class="psn-consistency__value">{{ $consistency['currentStreak'] }}</div>
                    <div class="psn-consistency__label">Current streak</div>
                    <div class="psn-consistency__sub">Consecutive days gaming</div>
                </div>
                <div class="psn-consistency__block">
                    <div class="psn-consistency__value">{{ $consistency['longestStreak'] }}</div>
                    <div class="psn-consistency__label">Longest streak</div>
                    <div class="psn-consistency__sub">Days in a row</div>
                </div>
                <div class="psn-consistency__block">
                    <div class="psn-consistency__value">{{ number_format($consistency['totalDays']) }}</div>
                    <div class="psn-consistency__label">Total days gamed</div>
                    <div class="psn-consistency__sub">Unique days with a session</div>
                </div>
                <div class="psn-consistency__block">
                    <div class="psn-consistency__value">{{ $consistency['avgSessionsPerWeek'] }}</div>
                    <div class="psn-consistency__label">Sessions per week</div>
                    <div class="psn-consistency__sub">Avg since first session</div>
                </div>
            </div>

            @if ($consistency['daysSinceLast'] !== null)
                <div class="psn-consistency__since">
                    @if ($consistency['daysSinceLast'] === 0)
                        Last played <strong>today</strong>
                    @elseif ($consistency['daysSinceLast'] === 1)
                        Last played <strong>yesterday</strong>
                    @else
                        Last played <strong>{{ $consistency['daysSinceLast'] }} days ago</strong>
                    @endif
                </div>
            @endif
        </x-ui.card>

        {{-- Backlog graveyard --}}
        @if ($backlogGraveyard->isNotEmpty())
            <x-ui.card title="Backlog graveyard 🪦" class="psn-stats-grid__wide">
                <p class="psn-graveyard__intro">Games not touched in 6+ months, sorted by hours invested — the silent debt in your library.</p>
                <div class="psn-graveyard">
                    @foreach ($backlogGraveyard as $game)
                        <a href="{{ route('playstation.show', $game['id']) }}" class="psn-graveyard__item">
                            @if ($game['image_url'])
                                <img src="{{ $game['image_url'] }}" class="psn-graveyard__cover" alt="">
                            @else
                                <div class="psn-graveyard__cover psn-graveyard__cover--empty"></div>
                            @endif
                            <div class="psn-graveyard__info">
                                <div class="psn-graveyard__title">{{ $game['label'] }}</div>
                                <div class="psn-graveyard__meta">
                                    <span class="gaming-platform-badge" style="background: #003087;">{{ $game['platform'] }}</span>
                                    {{ $game['hours'] }}h played
                                    @if ($game['completion'] > 0)
                                        · {{ $game['completion'] }}% complete
                                    @endif
                                </div>
                            </div>
                            <div class="psn-graveyard__right">
                                <div class="psn-graveyard__since">{{ $game['months_ago'] }} months ago</div>
                                <div class="psn-graveyard__date">{{ $game['last_played'] }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        {{-- Comeback games + Trophy velocity --}}
        @if ($comebackGames->isNotEmpty())
            <x-ui.card title="Comeback games">
                <div class="psn-comebacks">
                    @foreach ($comebackGames as $game)
                        <div class="psn-comeback">
                            @if ($game['image_url'])
                                <a href="{{ route('playstation.show', $game['game_id']) }}">
                                    <img src="{{ $game['image_url'] }}" class="psn-comeback__cover" alt="">
                                </a>
                            @endif
                            <div class="psn-comeback__info">
                                <a href="{{ route('playstation.show', $game['game_id']) }}" class="psn-comeback__title">{{ $game['label'] }}</a>
                                <div class="psn-comeback__gap">Returned after <strong>{{ $game['gap_days'] }} days</strong></div>
                                <div class="psn-comeback__dates">{{ $game['gap_start'] }} → {{ $game['gap_end'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        @if ($trophyVelocity->isNotEmpty())
            <x-ui.card title="Trophy velocity">
                <p class="psn-trophy-velocity__intro">Trophies earned per hour — which games give the most per session.</p>
                <div class="psn-trophy-velocity">
                    @foreach ($trophyVelocity as $game)
                        <div class="psn-trophy-velocity__item">
                            @if ($game['image_url'])
                                <a href="{{ route('playstation.show', $game['game_id']) }}">
                                    <img src="{{ $game['image_url'] }}" class="psn-trophy-velocity__cover" alt="">
                                </a>
                            @endif
                            <div class="psn-trophy-velocity__info">
                                <a href="{{ route('playstation.show', $game['game_id']) }}" class="psn-trophy-velocity__title">{{ $game['label'] }}</a>
                                <div class="psn-trophy-velocity__meta">{{ $game['earned'] }} 🏆 · {{ $game['hours'] }}h</div>
                            </div>
                            <div class="psn-trophy-velocity__rate">{{ $game['per_hour'] }}/h</div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

    </div>

    {{-- Trophy Deep Dive --}}
    @if($trophyDeepDive['bestDay'] || $trophyDeepDive['rarestTrophy'])
        <div x-data="{ open: false }" class="card mt-6">
            <div class="card__header" style="cursor: pointer;" @click="open = !open">
                <span class="card__header-title">💎 Trophy Deep Dive</span>
                <button class="btn btn--secondary btn--sm" x-text="open ? 'Collapse ▲' : 'Expand ▼'" @click.stop="open = !open"></button>
            </div>

            <div class="card__body" x-show="open" x-cloak>

                {{-- Row 1: 4 records --}}
                <div class="psn-records mb-6">

                    @if($trophyDeepDive['bestDay'])
                        <div class="psn-record">
                            <div class="psn-record__icon">🗓️</div>
                            <div class="psn-record__body">
                                <div class="psn-record__value">{{ $trophyDeepDive['bestDay']['count'] }}</div>
                                <div class="psn-record__label">Trophies in one day</div>
                                <div class="psn-record__sub">{{ $trophyDeepDive['bestDay']['date'] }}</div>
                            </div>
                        </div>
                    @endif

                    @if($trophyDeepDive['bestDow'])
                        <div class="psn-record">
                            <div class="psn-record__icon">📆</div>
                            <div class="psn-record__body">
                                <div class="psn-record__value">{{ $trophyDeepDive['bestDow'] }}</div>
                                <div class="psn-record__label">Best trophy day of the week</div>
                                <div class="psn-record__sub">{{ $trophyDeepDive['bestDowCount'] }} trophies total</div>
                            </div>
                        </div>
                    @endif

                    @if($trophyDeepDive['highestCompletionGame'])
                        @php $hc = $trophyDeepDive['highestCompletionGame']; @endphp
                        <div class="psn-record">
                            <div class="psn-record__icon">🏅</div>
                            <div class="psn-record__body">
                                <div class="psn-record__value">{{ $hc['pct'] }}%</div>
                                <div class="psn-record__label">Highest trophy completion</div>
                                <div class="psn-record__sub">
                                    <a href="{{ route('playstation.show', $hc['id']) }}" class="psn-record__link">{{ $hc['label'] }}</a>
                                </div>
                                <div class="psn-record__sub">{{ $hc['earned'] }} / {{ $hc['total'] }} trophies</div>
                            </div>
                        </div>
                    @endif

                    @if($trophyDeepDive['fastestPlatinum'])
                        @php $fp = $trophyDeepDive['fastestPlatinum']; @endphp
                        <div class="psn-record">
                            <div class="psn-record__icon">⚡</div>
                            <div class="psn-record__body">
                                <div class="psn-record__value">{{ $fp['days'] }}d</div>
                                <div class="psn-record__label">Fastest platinum</div>
                                <div class="psn-record__sub">
                                    <a href="{{ route('playstation.show', $fp['gameId']) }}" class="psn-record__link">{{ $fp['label'] }}</a>
                                </div>
                                <div class="psn-record__sub">Earned {{ $fp['earnedAt'] }}</div>
                            </div>
                        </div>
                    @endif

                    @if($trophyDeepDive['recentPlatinum'])
                        @php $rp = $trophyDeepDive['recentPlatinum']; @endphp
                        <div class="psn-record">
                            <div class="psn-record__icon">💎</div>
                            <div class="psn-record__body">
                                <div class="psn-record__value psn-record__value--sm">{{ $rp['earnedAt'] }}</div>
                                <div class="psn-record__label">Most recent platinum</div>
                                @if($rp['gameId'])
                                    <div class="psn-record__sub">
                                        <a href="{{ route('playstation.show', $rp['gameId']) }}" class="psn-record__link">{{ $rp['gameName'] }}</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($trophyDeepDive['rarestTrophy'])
                        @php $rt = $trophyDeepDive['rarestTrophy']; @endphp
                        <div class="psn-record">
                            <div class="psn-record__icon">✨</div>
                            <div class="psn-record__body">
                                <div class="psn-record__value psn-record__value--sm" style="color: {{ $rt['rarityColor'] }}">{{ $rt['rarityLabel'] }}</div>
                                <div class="psn-record__label">Rarest trophy earned</div>
                                <div class="psn-record__sub">{{ $rt['name'] }}</div>
                                @if($rt['gameId'])
                                    <div class="psn-record__sub">
                                        <a href="{{ route('playstation.show', $rt['gameId']) }}" class="psn-record__link">{{ $rt['gameName'] }}</a>
                                    </div>
                                @endif
                                <div class="psn-record__sub" style="color: {{ $rt['rarityColor'] }}">{{ number_format($rt['earnedRate'], 1) }}% of players</div>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Row 2: first / last trophy at 50% each --}}
                @if($trophyDeepDive['firstTrophy'] || $trophyDeepDive['lastTrophy'])
                    <div class="psn-records psn-records--two mb-6">

                        @if($trophyDeepDive['firstTrophy'])
                            @php $ft = $trophyDeepDive['firstTrophy']; @endphp
                            <div class="psn-record">
                                <div class="psn-record__icon">🌅</div>
                                <div class="psn-record__body">
                                    <div class="psn-record__value psn-record__value--sm">{{ $ft['earnedAt'] }}</div>
                                    <div class="psn-record__label">First trophy ever</div>
                                    <div class="psn-record__sub">{{ $ft['name'] }}</div>
                                    @if($ft['gameId'])
                                        <div class="psn-record__sub">
                                            <a href="{{ route('playstation.show', $ft['gameId']) }}" class="psn-record__link">{{ $ft['gameName'] }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($trophyDeepDive['lastTrophy'])
                            @php $lt = $trophyDeepDive['lastTrophy']; @endphp
                            <div class="psn-record">
                                <div class="psn-record__icon">🌙</div>
                                <div class="psn-record__body">
                                    <div class="psn-record__value psn-record__value--sm">{{ $lt['earnedAt'] }}</div>
                                    <div class="psn-record__label">Last trophy earned</div>
                                    <div class="psn-record__sub">{{ $lt['name'] }}</div>
                                    @if($lt['gameId'])
                                        <div class="psn-record__sub">
                                            <a href="{{ route('playstation.show', $lt['gameId']) }}" class="psn-record__link">{{ $lt['gameName'] }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                    </div>
                @endif

                {{-- Rarity distribution bar --}}
                @if($trophyDeepDive['rarityData']->isNotEmpty())
                    <div>
                        <div class="psn-trophy-chart__label">Rarity distribution — {{ number_format($trophyDeepDive['rarityTotal']) }} trophies with rarity data</div>
                        <div class="psn-rarity-bar">
                            @foreach($trophyDeepDive['rarityData'] as $r)
                                @if($r['pct'] > 0)
                                    <div class="psn-rarity-bar__segment"
                                         style="flex: {{ $r['pct'] }}; background: {{ $r['color'] }};"
                                         title="{{ $r['label'] }}: {{ $r['count'] }} ({{ $r['pct'] }}%)"></div>
                                @endif
                            @endforeach
                        </div>
                        <div class="psn-rarity-legend">
                            @foreach($trophyDeepDive['rarityData'] as $r)
                                <div class="psn-rarity-legend__item">
                                    <span class="psn-rarity-legend__dot" style="background: {{ $r['color'] }};"></span>
                                    <span class="psn-rarity-legend__label">{{ $r['label'] }}</span>
                                    <span class="psn-rarity-legend__count">{{ $r['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

</x-layouts.app>
