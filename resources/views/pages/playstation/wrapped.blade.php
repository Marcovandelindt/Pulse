<x-layouts.app title="PlayStation Wrapped {{ $wrapped['year'] ?? '' }}">

<div class="psn-wrapped">

    {{-- Header --}}
    <div class="psn-wrapped__header">
        <div class="psn-wrapped__header-left">
            <a href="{{ route('playstation.stats') }}" class="btn btn--secondary btn--sm">&larr; Stats</a>
            <h1 class="psn-wrapped__title">
                <span class="psn-wrapped__title-year">{{ $wrapped['year'] }}</span>
                PlayStation Wrapped
            </h1>
        </div>

        @if(count($availableYears) > 1)
            <div class="psn-wrapped__year-picker">
                @foreach($availableYears as $y)
                    <a href="{{ route('playstation.wrapped', ['year' => $y]) }}"
                       class="psn-wrapped__year-btn {{ $y === $wrapped['year'] ? 'psn-wrapped__year-btn--active' : '' }}">
                        {{ $y }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if(!($wrapped['hasData'] ?? false))
        <div class="psn-wrapped__empty">
            <p>No gaming sessions recorded for {{ $wrapped['year'] }}.</p>
        </div>
    @else

    {{-- ① Hero --}}
    <div class="psn-wrapped__hero">
        <div class="psn-wrapped__hero-stat">
            <div class="psn-wrapped__hero-value">{{ number_format($wrapped['totalHours'], 1) }}<span class="psn-wrapped__hero-unit">h</span></div>
            <div class="psn-wrapped__hero-label">Hours played</div>
        </div>
        <div class="psn-wrapped__hero-divider"></div>
        <div class="psn-wrapped__hero-stat">
            <div class="psn-wrapped__hero-value">{{ number_format($wrapped['totalSessions']) }}</div>
            <div class="psn-wrapped__hero-label">Sessions</div>
        </div>
        <div class="psn-wrapped__hero-divider"></div>
        <div class="psn-wrapped__hero-stat">
            <div class="psn-wrapped__hero-value">{{ $wrapped['uniqueGames'] }}</div>
            <div class="psn-wrapped__hero-label">Games played</div>
        </div>
        <div class="psn-wrapped__hero-divider"></div>
        <div class="psn-wrapped__hero-stat">
            <div class="psn-wrapped__hero-value">{{ $wrapped['newGames'] }}</div>
            <div class="psn-wrapped__hero-label">New games started</div>
        </div>
        @if($wrapped['vsLastYear'] !== null)
            <div class="psn-wrapped__hero-divider"></div>
            <div class="psn-wrapped__hero-stat">
                <div class="psn-wrapped__hero-value psn-wrapped__hero-value--{{ $wrapped['vsLastYear'] >= 0 ? 'up' : 'down' }}">
                    {{ $wrapped['vsLastYear'] >= 0 ? '+' : '' }}{{ $wrapped['vsLastYear'] }}%
                </div>
                <div class="psn-wrapped__hero-label">vs {{ $wrapped['year'] - 1 }}</div>
            </div>
        @endif
    </div>

    {{-- ② By the numbers --}}
    <div class="psn-wrapped__numbers">
        <div class="psn-wrapped__number">
            <span class="psn-wrapped__number-value">{{ $wrapped['totalDaysPlayed'] }}</span>
            <span class="psn-wrapped__number-label">days with at least one session</span>
        </div>
        <div class="psn-wrapped__number">
            <span class="psn-wrapped__number-value">{{ $wrapped['avgSessionFormatted'] }}</span>
            <span class="psn-wrapped__number-label">average session length</span>
        </div>
        <div class="psn-wrapped__number">
            <span class="psn-wrapped__number-value">{{ $wrapped['avgHoursPerDay'] }}h</span>
            <span class="psn-wrapped__number-label">average on days you played</span>
        </div>
        @if($wrapped['nightOwl']['count'] > 0)
            <div class="psn-wrapped__number">
                <span class="psn-wrapped__number-value">{{ $wrapped['nightOwl']['count'] }}</span>
                <span class="psn-wrapped__number-label">sessions started after midnight 🌙</span>
            </div>
        @endif
    </div>

    <div class="psn-wrapped__grid">

        {{-- ③ Top 5 games --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">Top games of {{ $wrapped['year'] }}</h2>
            <div class="psn-wrapped__top-games">
                @foreach($wrapped['topGames'] as $i => $game)
                    <a href="{{ route('playstation.show', $game['id']) }}" class="psn-wrapped__game {{ $i === 0 ? 'psn-wrapped__game--first' : '' }}">
                        <div class="psn-wrapped__game-rank">#{{ $i + 1 }}</div>
                        @if($game['image_url'])
                            <img src="{{ $game['image_url'] }}" alt="{{ $game['label'] }}" class="psn-wrapped__game-art">
                        @else
                            <div class="psn-wrapped__game-art psn-wrapped__game-art--placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:1.5rem;height:1.5rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                </svg>
                            </div>
                        @endif
                        <div class="psn-wrapped__game-info">
                            <div class="psn-wrapped__game-name">{{ $game['label'] }}</div>
                            <div class="psn-wrapped__game-meta">
                                <span class="psn-wrapped__game-hours">{{ $game['hours'] }}h</span>
                                <span>·</span>
                                <span>{{ $game['session_count'] }} {{ Str::plural('session', $game['session_count']) }}</span>
                                <span class="badge badge--muted" style="font-size: 0.7rem; padding: 0.125rem 0.4rem;">{{ $game['platform'] }}</span>
                            </div>
                        </div>
                        @if($i === 0)
                            <div class="psn-wrapped__game-crown">👑</div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ④ New games --}}
        @if($wrapped['newGamesDetail']['count'] > 0)
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">New discoveries</h2>
            <div class="psn-wrapped__new-games">
                <div class="psn-wrapped__new-summary">
                    <div class="psn-wrapped__new-stat">
                        <div class="psn-wrapped__new-stat-value">{{ $wrapped['newGamesDetail']['count'] }}</div>
                        <div class="psn-wrapped__new-stat-label">new games played</div>
                    </div>
                    <div class="psn-wrapped__new-stat">
                        <div class="psn-wrapped__new-stat-value">{{ $wrapped['newGamesDetail']['percent'] }}%</div>
                        <div class="psn-wrapped__new-stat-label">of all games you played were new</div>
                    </div>
                    <div class="psn-wrapped__new-stat">
                        <div class="psn-wrapped__new-stat-value">{{ $wrapped['newGamesDetail']['totalHours'] }}h</div>
                        <div class="psn-wrapped__new-stat-label">spent on new games</div>
                    </div>
                </div>

                <div class="psn-wrapped__new-list">
                    @foreach($wrapped['newGamesDetail']['top'] as $i => $game)
                        <a href="{{ route('playstation.show', $game['id']) }}" class="psn-wrapped__new-game">
                            <div class="psn-wrapped__new-game-rank">#{{ $i + 1 }}</div>
                            @if($game['image_url'])
                                <img src="{{ $game['image_url'] }}" alt="{{ $game['label'] }}" class="psn-wrapped__new-game-art">
                            @else
                                <div class="psn-wrapped__new-game-art psn-wrapped__new-game-art--placeholder"></div>
                            @endif
                            <div class="psn-wrapped__new-game-info">
                                <div class="psn-wrapped__new-game-name">{{ $game['label'] }}</div>
                                <div class="psn-wrapped__new-game-meta">
                                    <span>{{ $game['hours'] }}h</span>
                                    <span>·</span>
                                    <span>{{ $game['session_count'] }} {{ Str::plural('session', $game['session_count']) }}</span>
                                    <span class="badge badge--muted" style="font-size: 0.7rem; padding: 0.125rem 0.4rem;">{{ $game['platform'] }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ⑤ When you played —  time of day + weekday vs weekend --}}
        <div class="psn-wrapped__section">
            <h2 class="psn-wrapped__section-title">Time of day</h2>
            <div class="psn-wrapped__tod">
                @foreach($wrapped['timeOfDay'] as $key => $slot)
                    <div class="psn-wrapped__tod-row {{ $slot['percent'] === max(array_column($wrapped['timeOfDay'], 'percent')) ? 'psn-wrapped__tod-row--best' : '' }}">
                        <div class="psn-wrapped__tod-icon">{{ $slot['icon'] }}</div>
                        <div class="psn-wrapped__tod-info">
                            <div class="psn-wrapped__tod-label">{{ $slot['label'] }} <span class="psn-wrapped__tod-range">{{ $slot['range'] }}</span></div>
                            <div class="psn-wrapped__tod-bar-wrap">
                                <div class="psn-wrapped__tod-bar" style="width: {{ $slot['percent'] }}%;"></div>
                            </div>
                        </div>
                        <div class="psn-wrapped__tod-value">{{ $slot['hours'] }}h <span class="psn-wrapped__tod-pct">{{ $slot['percent'] }}%</span></div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ⑤ Day of week --}}
        <div class="psn-wrapped__section">
            <h2 class="psn-wrapped__section-title">Day of week</h2>
            <div class="psn-wrapped__weekdays">
                @foreach($wrapped['weekdayBreakdown'] as $day)
                    <div class="psn-wrapped__weekday-col">
                        <div class="psn-wrapped__weekday-bar-wrap">
                            <div class="psn-wrapped__weekday-bar {{ $day['percent'] === 100 ? 'psn-wrapped__weekday-bar--best' : '' }}"
                                 style="height: {{ max($day['percent'], $day['hours'] > 0 ? 4 : 0) }}%;"></div>
                        </div>
                        <div class="psn-wrapped__weekday-label">{{ $day['label'] }}</div>
                        @if($day['hours'] > 0)
                            <div class="psn-wrapped__weekday-hours">{{ $day['hours'] }}h</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="psn-wrapped__split">
                <div class="psn-wrapped__split-item">
                    <div class="psn-wrapped__split-value">{{ $wrapped['weekdayVsWeekend']['weekday']['hours'] }}h</div>
                    <div class="psn-wrapped__split-label">Weekdays · {{ $wrapped['weekdayVsWeekend']['weekday']['percent'] }}%</div>
                </div>
                <div class="psn-wrapped__split-divider"></div>
                <div class="psn-wrapped__split-item">
                    <div class="psn-wrapped__split-value">{{ $wrapped['weekdayVsWeekend']['weekend']['hours'] }}h</div>
                    <div class="psn-wrapped__split-label">Weekends · {{ $wrapped['weekdayVsWeekend']['weekend']['percent'] }}%</div>
                </div>
            </div>
        </div>

        {{-- ⑥ Monthly activity --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">Month by month</h2>
            <div class="psn-wrapped__month-chart">
                @foreach($wrapped['monthlyChart'] as $month)
                    <div class="psn-wrapped__month-col">
                        <div class="psn-wrapped__month-bar-wrap">
                            <div class="psn-wrapped__month-bar {{ $month['percent'] === 100 ? 'psn-wrapped__month-bar--best' : '' }}"
                                 style="height: {{ max($month['percent'], $month['hours'] > 0 ? 4 : 0) }}%;"></div>
                        </div>
                        <div class="psn-wrapped__month-label">{{ $month['label'] }}</div>
                        @if($month['hours'] > 0)
                            <div class="psn-wrapped__month-hours">{{ $month['hours'] }}h</div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="psn-wrapped__month-footer">
                @if($wrapped['bestMonth']['name'])
                    <span>Best month: <strong>{{ $wrapped['bestMonth']['name'] }}</strong> · {{ $wrapped['bestMonth']['hours'] }}h · {{ $wrapped['bestMonth']['sessions'] }} sessions</span>
                @endif
                @if($wrapped['busiestWeek'])
                    <span>Busiest week: <strong>{{ $wrapped['busiestWeek']['label'] }}</strong> · {{ $wrapped['busiestWeek']['hours'] }}h</span>
                @endif
            </div>
        </div>

        {{-- ⑦ Record breakers --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">Record breakers</h2>
            <div class="psn-wrapped__records">

                @if($wrapped['busiestDay'])
                    <div class="psn-wrapped__record psn-wrapped__record--wide">
                        <div class="psn-wrapped__record-icon">🔥</div>
                        <div class="psn-wrapped__record-body">
                            <div class="psn-wrapped__record-value">{{ $wrapped['busiestDay']['formatted'] }}</div>
                            <div class="psn-wrapped__record-label">Most played in one day</div>
                            <div class="psn-wrapped__record-sub">{{ $wrapped['busiestDay']['date'] }} · {{ $wrapped['busiestDay']['sessions'] }} {{ Str::plural('session', $wrapped['busiestDay']['sessions']) }}</div>
                            @if($wrapped['busiestDay']['breakdown']->isNotEmpty())
                                <div class="psn-wrapped__day-breakdown">
                                    @foreach($wrapped['busiestDay']['breakdown'] as $s)
                                        <div class="psn-wrapped__day-session">
                                            <span class="psn-wrapped__day-session-time">{{ $s['time'] }}</span>
                                            <span class="psn-wrapped__day-session-duration">{{ $s['duration'] }}</span>
                                            <a href="{{ route('playstation.show', $s['gameId']) }}" class="psn-wrapped__day-session-game">{{ $s['game'] }}</a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($wrapped['longestSession'])
                    <div class="psn-wrapped__record">
                        <div class="psn-wrapped__record-icon">⏱️</div>
                        <div class="psn-wrapped__record-body">
                            <div class="psn-wrapped__record-value">{{ $wrapped['longestSession']['duration'] }}</div>
                            <div class="psn-wrapped__record-label">Longest single session</div>
                            <div class="psn-wrapped__record-sub">
                                {{ $wrapped['longestSession']['date'] }} at {{ $wrapped['longestSession']['time'] }}
                                @if($wrapped['longestSession']['game'])
                                    · <a href="{{ route('playstation.show', $wrapped['longestSession']['gameId']) }}" class="psn-wrapped__link">{{ $wrapped['longestSession']['game'] }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($wrapped['longestStreak']['days'] > 1)
                    <div class="psn-wrapped__record">
                        <div class="psn-wrapped__record-icon">📆</div>
                        <div class="psn-wrapped__record-body">
                            <div class="psn-wrapped__record-value">{{ $wrapped['longestStreak']['days'] }} days</div>
                            <div class="psn-wrapped__record-label">Longest playing streak</div>
                            <div class="psn-wrapped__record-sub">{{ $wrapped['longestStreak']['startDate'] }} – {{ $wrapped['longestStreak']['endDate'] }}</div>
                        </div>
                    </div>
                @endif

                @if($wrapped['busiestWeek'])
                    <div class="psn-wrapped__record">
                        <div class="psn-wrapped__record-icon">📊</div>
                        <div class="psn-wrapped__record-body">
                            <div class="psn-wrapped__record-value">{{ $wrapped['busiestWeek']['hours'] }}h</div>
                            <div class="psn-wrapped__record-label">Most played week</div>
                            <div class="psn-wrapped__record-sub">{{ $wrapped['busiestWeek']['label'] }} · {{ $wrapped['busiestWeek']['sessions'] }} sessions</div>
                            @if($wrapped['busiestWeek']['topGame'])
                                <div class="psn-wrapped__record-sub" style="margin-top: 0.25rem;">
                                    Most played: <a href="{{ route('playstation.show', $wrapped['busiestWeek']['topGameId']) }}" class="psn-wrapped__link">{{ $wrapped['busiestWeek']['topGame'] }}</a>
                                    · {{ $wrapped['busiestWeek']['topGameHours'] }}h
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- ⑧ Trophies --}}
        @if($wrapped['trophies']['total'] > 0)
            <div class="psn-wrapped__section">
                <h2 class="psn-wrapped__section-title">Trophy haul</h2>
                <div class="psn-wrapped__trophies">
                    <div class="psn-wrapped__trophy-total">{{ number_format($wrapped['trophies']['total']) }}</div>
                    <div class="psn-wrapped__trophy-label">trophies earned in {{ $wrapped['year'] }}</div>

                    <div class="psn-wrapped__trophy-types">
                        @if($wrapped['trophies']['platinum'] > 0)
                            <div class="psn-wrapped__trophy-type">
                                <span class="psn-wrapped__trophy-icon">🏆</span>
                                <span class="psn-wrapped__trophy-count">{{ $wrapped['trophies']['platinum'] }}</span>
                                <span class="psn-wrapped__trophy-name">Platinum</span>
                            </div>
                        @endif
                        <div class="psn-wrapped__trophy-type">
                            <span class="psn-wrapped__trophy-icon" style="color:#c9a227;">🥇</span>
                            <span class="psn-wrapped__trophy-count">{{ $wrapped['trophies']['gold'] }}</span>
                            <span class="psn-wrapped__trophy-name">Gold</span>
                        </div>
                        <div class="psn-wrapped__trophy-type">
                            <span class="psn-wrapped__trophy-icon" style="color:#9ea3a8;">🥈</span>
                            <span class="psn-wrapped__trophy-count">{{ $wrapped['trophies']['silver'] }}</span>
                            <span class="psn-wrapped__trophy-name">Silver</span>
                        </div>
                        <div class="psn-wrapped__trophy-type">
                            <span class="psn-wrapped__trophy-icon" style="color:#cd7f32;">🥉</span>
                            <span class="psn-wrapped__trophy-count">{{ $wrapped['trophies']['bronze'] }}</span>
                            <span class="psn-wrapped__trophy-name">Bronze</span>
                        </div>
                    </div>

                    @if($wrapped['bestTrophyDay'])
                        <div class="psn-wrapped__trophy-fact">
                            Best trophy day: <strong>{{ $wrapped['bestTrophyDay']['day'] }}, {{ $wrapped['bestTrophyDay']['date'] }}</strong> — {{ $wrapped['bestTrophyDay']['count'] }} trophies popped
                        </div>
                    @endif
                </div>
            </div>

            @if($wrapped['topTrophyGame'])
                <div class="psn-wrapped__section">
                    <h2 class="psn-wrapped__section-title">Most trophies from one game</h2>
                    <a href="{{ route('playstation.show', $wrapped['topTrophyGame']['gameId']) }}" class="psn-wrapped__trophy-game">
                        @if($wrapped['topTrophyGame']['image_url'])
                            <img src="{{ $wrapped['topTrophyGame']['image_url'] }}" alt="{{ $wrapped['topTrophyGame']['label'] }}" class="psn-wrapped__trophy-game-art">
                        @endif
                        <div class="psn-wrapped__trophy-game-info">
                            <div class="psn-wrapped__trophy-game-name">{{ $wrapped['topTrophyGame']['label'] }}</div>
                            <div class="psn-wrapped__trophy-game-count">{{ $wrapped['topTrophyGame']['count'] }} trophies earned in {{ $wrapped['year'] }}</div>
                        </div>
                    </a>
                </div>
            @endif
        @endif

        {{-- ⑨ Platform breakdown --}}
        @if($wrapped['platformBreakdown']->count() > 1)
            <div class="psn-wrapped__section psn-wrapped__section--full">
                <h2 class="psn-wrapped__section-title">Platform breakdown</h2>
                <div class="psn-wrapped__platforms">
                    @foreach($wrapped['platformBreakdown'] as $p)
                        <div class="psn-wrapped__platform">
                            <div class="psn-wrapped__platform-header">
                                <span class="psn-wrapped__platform-name">{{ $p['platform'] }}</span>
                                <span class="psn-wrapped__platform-meta">{{ $p['hours'] }}h · {{ $p['sessions'] }} sessions · {{ $p['game_count'] }} {{ Str::plural('game', $p['game_count']) }}</span>
                            </div>
                            <div class="psn-wrapped__platform-track">
                                <div class="psn-wrapped__platform-fill" style="width: {{ $p['percent'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ⑩ First & last session --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">The beginning and the end</h2>
            <div class="psn-wrapped__bookends">

                @if($wrapped['firstSession'])
                    <div class="psn-wrapped__bookend">
                        <div class="psn-wrapped__bookend-badge">First session</div>
                        <div class="psn-wrapped__bookend-datetime">
                            {{ $wrapped['firstSession']['date'] }}
                            <span class="psn-wrapped__bookend-time">at {{ $wrapped['firstSession']['time'] }}</span>
                        </div>
                        @if($wrapped['firstSession']['game'])
                            <a href="{{ route('playstation.show', $wrapped['firstSession']['gameId']) }}" class="psn-wrapped__bookend-game">
                                {{ $wrapped['firstSession']['game'] }}
                            </a>
                        @endif
                        <div class="psn-wrapped__bookend-duration">{{ $wrapped['firstSession']['duration'] }} played</div>
                    </div>
                @endif

                <div class="psn-wrapped__bookend-arrow">→</div>

                @if($wrapped['lastSession'])
                    <div class="psn-wrapped__bookend">
                        <div class="psn-wrapped__bookend-badge psn-wrapped__bookend-badge--last">Last session</div>
                        <div class="psn-wrapped__bookend-datetime">
                            {{ $wrapped['lastSession']['date'] }}
                            <span class="psn-wrapped__bookend-time">at {{ $wrapped['lastSession']['time'] }}</span>
                        </div>
                        @if($wrapped['lastSession']['game'])
                            <a href="{{ route('playstation.show', $wrapped['lastSession']['gameId']) }}" class="psn-wrapped__bookend-game">
                                {{ $wrapped['lastSession']['game'] }}
                            </a>
                        @endif
                        <div class="psn-wrapped__bookend-duration">{{ $wrapped['lastSession']['duration'] }} played</div>
                    </div>
                @endif

            </div>
        </div>

    </div>
    @endif

</div>

</x-layouts.app>
