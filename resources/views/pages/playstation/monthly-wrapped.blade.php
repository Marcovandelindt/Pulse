<x-layouts.app title="PlayStation Monthly — {{ ($wrapped['monthName'] ?? '') . ' ' . ($wrapped['year'] ?? '') }}">

<div class="psn-wrapped">

    {{-- Header --}}
    <div class="psn-wrapped__header">
        <div class="psn-wrapped__header-left">
            <a href="{{ route('playstation.wrapped') }}" class="btn btn--secondary btn--sm">&larr; Yearly</a>
            <h1 class="psn-wrapped__title">
                <span class="psn-wrapped__title-year">{{ $wrapped['monthName'] ?? '' }} {{ $wrapped['year'] ?? '' }}</span>
                Monthly Wrapped
            </h1>
        </div>
    </div>

    {{-- Month navigator --}}
    <div class="psn-monthly-nav">
        @if($prevMonth)
            <a href="{{ route('playstation.monthly-wrapped', ['year' => $prevMonth['year'], 'month' => $prevMonth['month']]) }}"
               class="psn-monthly-nav__arrow">
                &larr; {{ $prevMonth['label'] }}
            </a>
        @else
            <span class="psn-monthly-nav__arrow psn-monthly-nav__arrow--disabled"></span>
        @endif

        <div class="psn-monthly-nav__current">
            {{ $wrapped['monthName'] ?? '' }} {{ $wrapped['year'] ?? '' }}
        </div>

        @if($nextMonth)
            <a href="{{ route('playstation.monthly-wrapped', ['year' => $nextMonth['year'], 'month' => $nextMonth['month']]) }}"
               class="psn-monthly-nav__arrow psn-monthly-nav__arrow--right">
                {{ $nextMonth['label'] }} &rarr;
            </a>
        @else
            <span class="psn-monthly-nav__arrow psn-monthly-nav__arrow--disabled"></span>
        @endif
    </div>

    @if(!($wrapped['hasData'] ?? false))
        <div class="psn-wrapped__empty">
            <p>No gaming sessions recorded for {{ $wrapped['monthName'] ?? '' }} {{ $wrapped['year'] ?? '' }}.</p>
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
            <div class="psn-wrapped__hero-value">{{ $wrapped['daysPlayed'] }}</div>
            <div class="psn-wrapped__hero-label">Days played</div>
        </div>
        @if($wrapped['vsPrevMonth'] !== null)
            <div class="psn-wrapped__hero-divider"></div>
            <div class="psn-wrapped__hero-stat">
                <div class="psn-wrapped__hero-value psn-wrapped__hero-value--{{ $wrapped['vsPrevMonth'] >= 0 ? 'up' : 'down' }}">
                    {{ $wrapped['vsPrevMonth'] >= 0 ? '+' : '' }}{{ $wrapped['vsPrevMonth'] }}%
                </div>
                <div class="psn-wrapped__hero-label">vs prev month</div>
            </div>
        @endif
    </div>

    {{-- ② By the numbers --}}
    <div class="psn-wrapped__numbers">
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
        @if($wrapped['trophies']['total'] > 0)
            <div class="psn-wrapped__number">
                <span class="psn-wrapped__number-value">{{ $wrapped['trophies']['total'] }}</span>
                <span class="psn-wrapped__number-label">trophies earned 🏆</span>
            </div>
        @endif
    </div>

    {{-- ② ½ Personality archetypes --}}
    @if(!empty($wrapped['personalityArchetype']))
    <div class="psn-archetype">
        <div class="psn-archetype__label">Your {{ $wrapped['monthName'] }} gaming style</div>
        <div class="psn-archetype__badges">
            @foreach($wrapped['personalityArchetype'] as $a)
                <div class="psn-archetype__badge psn-archetype__badge--{{ $a['id'] }}">
                    <span class="psn-archetype__badge-icon">{{ $a['icon'] }}</span>
                    <div class="psn-archetype__badge-text">
                        <div class="psn-archetype__badge-name">{{ $a['label'] }}</div>
                        <div class="psn-archetype__badge-desc">{{ $a['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="psn-wrapped__grid">

        {{-- ③ Top 3 games --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">Top games of {{ $wrapped['monthName'] }}</h2>
            <div class="psn-wrapped__top-games">
                @foreach($wrapped['topGames'] as $i => $game)
                    <a href="{{ route('playstation.show', $game['id']) }}" class="psn-wrapped__game {{ $i === 0 ? 'psn-wrapped__game--first' : '' }}">
                        <div class="psn-wrapped__game-rank">#{{ $i + 1 }}</div>
                        @if($game['image_url'])
                            <img src="{{ $game['image_url'] }}" alt="{{ $game['label'] }}" class="psn-wrapped__game-art">
                        @else
                            <div class="psn-wrapped__game-art psn-wrapped__game-art--placeholder"></div>
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
                        <div class="psn-monthly-share">{{ $game['share'] }}%</div>
                        @if($i === 0)
                            <div class="psn-wrapped__game-crown">👑</div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ④ New games this month --}}
        @if($wrapped['newGames']->isNotEmpty())
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">New this month</h2>
            <div class="psn-wrapped__new-list">
                @foreach($wrapped['newGames'] as $i => $game)
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
        @endif

        {{-- ⑤ Week by week --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">Week by week</h2>
            <div class="psn-weekly-chart">
                @foreach($wrapped['weeklyChart'] as $week)
                    <div class="psn-weekly-chart__col">
                        <div class="psn-weekly-chart__bar-wrap">
                            <div class="psn-weekly-chart__bar {{ $week['percent'] === 100 ? 'psn-weekly-chart__bar--best' : '' }}"
                                 style="height: {{ max($week['percent'], $week['hours'] > 0 ? 6 : 0) }}%;"></div>
                        </div>
                        <div class="psn-weekly-chart__label">{{ $week['label'] }}</div>
                        <div class="psn-weekly-chart__range">{{ $week['range'] }}</div>
                        @if($week['hours'] > 0)
                            <div class="psn-weekly-chart__hours">{{ $week['hours'] }}h</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ⑥ Time of day + Day of week --}}
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

            </div>
        </div>

        {{-- ⑧ Trophies --}}
        @if($wrapped['trophies']['total'] > 0)
            <div class="psn-wrapped__section">
                <h2 class="psn-wrapped__section-title">Trophy haul</h2>
                <div class="psn-wrapped__trophies">
                    <div class="psn-wrapped__trophy-total">{{ number_format($wrapped['trophies']['total']) }}</div>
                    <div class="psn-wrapped__trophy-label">trophies earned in {{ $wrapped['monthName'] }}</div>

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
                        @else
                            <div class="psn-wrapped__trophy-game-art psn-wrapped__trophy-game-art--placeholder"></div>
                        @endif
                        <div class="psn-wrapped__trophy-game-info">
                            <div class="psn-wrapped__trophy-game-name">{{ $wrapped['topTrophyGame']['label'] }}</div>
                            <div class="psn-wrapped__trophy-game-count">{{ $wrapped['topTrophyGame']['count'] }} trophies earned</div>
                        </div>
                    </a>
                </div>
            @else
                <div class="psn-wrapped__section"></div>
            @endif
        @endif

        {{-- ⑨ First & last session --}}
        @if($wrapped['firstSession'] || $wrapped['lastSession'])
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">First & last session</h2>
            <div class="psn-wrapped__bookends">
                @if($wrapped['firstSession'])
                    <div class="psn-wrapped__bookend">
                        <div class="psn-wrapped__bookend-icon">🌱</div>
                        <div class="psn-wrapped__bookend-body">
                            <div class="psn-wrapped__bookend-label">First session</div>
                            <div class="psn-wrapped__bookend-value">
                                @if($wrapped['firstSession']['game'])
                                    <a href="{{ route('playstation.show', $wrapped['firstSession']['gameId']) }}" class="psn-wrapped__link">{{ $wrapped['firstSession']['game'] }}</a>
                                @endif
                            </div>
                            <div class="psn-wrapped__bookend-meta">{{ $wrapped['firstSession']['date'] }} at {{ $wrapped['firstSession']['time'] }} · {{ $wrapped['firstSession']['duration'] }}</div>
                        </div>
                    </div>
                @endif
                @if($wrapped['lastSession'])
                    <div class="psn-wrapped__bookend">
                        <div class="psn-wrapped__bookend-icon">🏁</div>
                        <div class="psn-wrapped__bookend-body">
                            <div class="psn-wrapped__bookend-label">Last session</div>
                            <div class="psn-wrapped__bookend-value">
                                @if($wrapped['lastSession']['game'])
                                    <a href="{{ route('playstation.show', $wrapped['lastSession']['gameId']) }}" class="psn-wrapped__link">{{ $wrapped['lastSession']['game'] }}</a>
                                @endif
                            </div>
                            <div class="psn-wrapped__bookend-meta">{{ $wrapped['lastSession']['date'] }} at {{ $wrapped['lastSession']['time'] }} · {{ $wrapped['lastSession']['duration'] }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ⑩ Calendar heatmap --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <h2 class="psn-wrapped__section-title">{{ $wrapped['monthName'] }} at a glance</h2>
            <div class="psn-monthly-heatmap">
                <div class="psn-monthly-heatmap__days-header">
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayLabel)
                        <div class="psn-monthly-heatmap__day-label">{{ $dayLabel }}</div>
                    @endforeach
                </div>
                <div class="psn-monthly-heatmap__grid">
                    @foreach($wrapped['calendarHeatmap'] as $week)
                        @foreach($week as $cell)
                            <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--{{ $cell['level'] === -1 ? 'empty' : 'l' . $cell['level'] }}"
                                 @if($cell['in_month'] && $cell['minutes'] > 0)
                                     title="{{ $cell['date'] }}: {{ $cell['formatted'] }}"
                                 @endif>
                                @if($cell['in_month'])
                                    <span class="psn-monthly-heatmap__day-num">{{ $cell['day'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
                <div class="psn-monthly-heatmap__legend">
                    <span>Less</span>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--l0"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--l1"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--l2"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--l3"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--l4"></div>
                    <span>More</span>
                </div>
            </div>
        </div>

    </div>{{-- /.psn-wrapped__grid --}}

    @endif

</div>

</x-layouts.app>
