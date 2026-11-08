<x-layouts.app title="Music Monthly — {{ ($wrapped['monthName'] ?? '') . ' ' . ($wrapped['year'] ?? '') }}">

<div class="psn-wrapped">

    {{-- Header --}}
    <div class="psn-wrapped__header">
        <div class="psn-wrapped__header-left">
            <a href="{{ route('music.stats') }}" class="btn btn--secondary btn--sm">&larr; Stats</a>
            <h1 class="psn-wrapped__title">
                <span class="psn-wrapped__title-year">{{ $wrapped['monthName'] ?? '' }} {{ $wrapped['year'] ?? '' }}</span>
                Monthly Wrapped
            </h1>
        </div>
    </div>

    {{-- Month navigator --}}
    <div class="psn-monthly-nav">
        @if($prevMonth)
            <a href="{{ route('music.monthly-wrapped', ['year' => $prevMonth['year'], 'month' => $prevMonth['month']]) }}"
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
            <a href="{{ route('music.monthly-wrapped', ['year' => $nextMonth['year'], 'month' => $nextMonth['month']]) }}"
               class="psn-monthly-nav__arrow psn-monthly-nav__arrow--right">
                {{ $nextMonth['label'] }} &rarr;
            </a>
        @else
            <span class="psn-monthly-nav__arrow psn-monthly-nav__arrow--disabled"></span>
        @endif
    </div>

    @if(!($wrapped['hasData'] ?? false))
        <div class="psn-wrapped__empty">
            <p>No plays recorded for {{ $wrapped['monthName'] ?? '' }} {{ $wrapped['year'] ?? '' }}.</p>
        </div>
    @else

    {{-- ① Hero --}}
    <div class="psn-wrapped__hero">
        <div class="psn-wrapped__hero-stat">
            <div class="psn-wrapped__hero-value">{{ number_format($wrapped['totalPlays']) }}</div>
            <div class="psn-wrapped__hero-label">Plays</div>
        </div>
        <div class="psn-wrapped__hero-divider"></div>
        <div class="psn-wrapped__hero-stat">
            @php $hrs = (int) floor($wrapped['totalMinutes'] / 60); $mins = $wrapped['totalMinutes'] % 60; @endphp
            <div class="psn-wrapped__hero-value">
                {{ $hrs }}<span class="psn-wrapped__hero-unit">h</span>
                @if($mins > 0)<span style="font-size:1.5rem;font-weight:600;color:var(--color-text-muted)"> {{ $mins }}m</span>@endif
            </div>
            <div class="psn-wrapped__hero-label">Listened</div>
        </div>
        <div class="psn-wrapped__hero-divider"></div>
        <div class="psn-wrapped__hero-stat">
            <div class="psn-wrapped__hero-value">{{ number_format($wrapped['uniqueTracks']) }}</div>
            <div class="psn-wrapped__hero-label">Unique tracks</div>
        </div>
        <div class="psn-wrapped__hero-divider"></div>
        <div class="psn-wrapped__hero-stat">
            <div class="psn-wrapped__hero-value">{{ $wrapped['uniqueArtists'] }}</div>
            <div class="psn-wrapped__hero-label">Artists</div>
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
            <span class="psn-wrapped__number-value">{{ number_format($wrapped['avgPlaysPerDay'], 1) }}</span>
            <span class="psn-wrapped__number-label">plays per day on average</span>
        </div>
        <div class="psn-wrapped__number">
            <span class="psn-wrapped__number-value">{{ $wrapped['daysPlayed'] }}</span>
            <span class="psn-wrapped__number-label">days with at least one play</span>
        </div>
        @if($wrapped['longestStreak']['days'] > 1)
            <div class="psn-wrapped__number">
                <span class="psn-wrapped__number-value">{{ $wrapped['longestStreak']['days'] }}</span>
                <span class="psn-wrapped__number-label">day listening streak 🔥</span>
            </div>
        @endif
    </div>

    {{-- ③ Personality --}}
    @if(!empty($wrapped['personalityArchetype']))
        <div class="psn-wrapped__archetypes">
            @foreach($wrapped['personalityArchetype'] as $archetype)
                <div class="psn-wrapped__archetype">
                    <span class="psn-wrapped__archetype-icon">{{ $archetype['icon'] }}</span>
                    <div>
                        <div class="psn-wrapped__archetype-label">{{ $archetype['label'] }}</div>
                        <div class="psn-wrapped__archetype-desc">{{ $archetype['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="psn-wrapped__grid">

        {{-- ④ Top Tracks --}}
        @if(!empty($wrapped['topTracks']))
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <div class="psn-wrapped__section-title">Top tracks</div>
            <div class="music-monthly-track-list">
                @foreach($wrapped['topTracks'] as $i => $track)
                    <div class="music-monthly-track {{ $i === 0 ? 'music-monthly-track--first' : '' }}">
                        <span class="music-monthly-track__rank">
                            @if($i === 0) 🥇 @elseif($i === 1) 🥈 @elseif($i === 2) 🥉 @else {{ $i + 1 }} @endif
                        </span>
                        @if($track['image_url'])
                            <img src="{{ $track['image_url'] }}" alt="" class="music-monthly-track__art">
                        @else
                            <div class="music-monthly-track__art music-monthly-track__art--empty"></div>
                        @endif
                        <div class="music-monthly-track__info">
                            <div class="music-monthly-track__title">{{ $track['title'] }}</div>
                            <div class="music-monthly-track__artist">{{ $track['artist'] }}</div>
                        </div>
                        <span class="music-monthly-track__count">{{ number_format($track['play_count']) }}×</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ⑤ Top Artists --}}
        @if(!empty($wrapped['topArtists']))
        <div class="psn-wrapped__section">
            <div class="psn-wrapped__section-title">Top artists</div>
            <div class="music-monthly-artist-list">
                @foreach($wrapped['topArtists'] as $i => $artist)
                    <div class="music-monthly-artist">
                        @if($artist['image_url'])
                            <img src="{{ $artist['image_url'] }}" alt="{{ $artist['name'] }}" class="music-monthly-artist__photo">
                        @else
                            <div class="music-monthly-artist__photo music-monthly-artist__photo--empty">
                                {{ mb_substr($artist['name'], 0, 1) }}
                            </div>
                        @endif
                        <div class="music-monthly-artist__info">
                            <div class="music-monthly-artist__name">{{ $artist['name'] }}</div>
                            <div class="music-monthly-artist__count">{{ number_format($artist['play_count']) }} plays</div>
                        </div>
                        @if($i === 0)
                            <span style="font-size:1rem;">🏅</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ⑥ Top Albums --}}
        @if(!empty($wrapped['topAlbums']))
        <div class="psn-wrapped__section">
            <div class="psn-wrapped__section-title">Top albums</div>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                @foreach($wrapped['topAlbums'] as $album)
                    <div style="display:flex;align-items:center;gap:0.875rem;">
                        @if($album['image_url'])
                            <img src="{{ $album['image_url'] }}" alt="" style="width:3rem;height:3rem;border-radius:var(--radius-sm);object-fit:cover;flex-shrink:0;">
                        @else
                            <div style="width:3rem;height:3rem;border-radius:var(--radius-sm);background:var(--color-bg-tertiary);flex-shrink:0;"></div>
                        @endif
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.875rem;font-weight:600;color:var(--color-text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $album['name'] }}</div>
                            <div style="font-size:0.75rem;color:var(--color-text-muted);">{{ number_format($album['play_count']) }} plays</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ⑦ Time of day --}}
        <div class="psn-wrapped__section">
            <div class="psn-wrapped__section-title">Time of day</div>
            <div class="psn-wrapped__time-of-day">
                @foreach($wrapped['timeOfDay'] as $slot)
                    <div class="psn-wrapped__tod-row">
                        <span class="psn-wrapped__tod-icon">{{ $slot['icon'] }}</span>
                        <span class="psn-wrapped__tod-label">{{ $slot['label'] }}</span>
                        <div class="psn-wrapped__tod-bar-wrap">
                            <div class="psn-wrapped__tod-bar" style="width:{{ $slot['percent'] }}%"></div>
                        </div>
                        <span class="psn-wrapped__tod-value">{{ number_format($slot['plays']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ⑧ Day of week --}}
        <div class="psn-wrapped__section">
            <div class="psn-wrapped__section-title">Day of week</div>
            <div class="psn-wrapped__weekday-bars">
                @foreach($wrapped['weekdayBreakdown'] as $day)
                    <div class="psn-wrapped__weekday-col">
                        <div class="psn-wrapped__weekday-bar-wrap">
                            <div class="psn-wrapped__weekday-bar" style="height:{{ $day['percent'] }}%"></div>
                        </div>
                        <span class="psn-wrapped__weekday-label">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="psn-wrapped__wd-we">
                <div class="psn-wrapped__wd-we-stat">
                    <span class="psn-wrapped__wd-we-val">{{ $wrapped['weekdayVsWeekend']['weekday']['plays'] }}</span>
                    <span class="psn-wrapped__wd-we-label">Weekday plays ({{ $wrapped['weekdayVsWeekend']['weekday']['percent'] }}%)</span>
                </div>
                <div class="psn-wrapped__wd-we-stat">
                    <span class="psn-wrapped__wd-we-val">{{ $wrapped['weekdayVsWeekend']['weekend']['plays'] }}</span>
                    <span class="psn-wrapped__wd-we-label">Weekend plays ({{ $wrapped['weekdayVsWeekend']['weekend']['percent'] }}%)</span>
                </div>
            </div>
        </div>

        {{-- ⑨ Week-by-week --}}
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <div class="psn-wrapped__section-title">Week by week</div>
            <div class="psn-weekly-chart">
                @foreach($wrapped['weeklyChart'] as $week)
                    <div class="psn-weekly-chart__col">
                        <div class="psn-weekly-chart__bar-track">
                            <div class="psn-weekly-chart__bar-fill" style="height:{{ $week['percent'] }}%"></div>
                        </div>
                        <div class="psn-weekly-chart__meta">
                            <div class="psn-weekly-chart__label">{{ $week['label'] }}</div>
                            <div class="psn-weekly-chart__range">{{ $week['range'] }}</div>
                            <div class="psn-weekly-chart__value">{{ number_format($week['plays']) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ⑩ Record breakers --}}
        <div class="psn-wrapped__section">
            <div class="psn-wrapped__section-title">Record breakers</div>
            <div class="psn-wrapped__records">

                @if($wrapped['busiestDay'])
                    <div class="psn-wrapped__record">
                        <div class="psn-wrapped__record-icon">📅</div>
                        <div>
                            <div class="psn-wrapped__record-value">{{ $wrapped['busiestDay']['count'] }} plays</div>
                            <div class="psn-wrapped__record-label">Busiest day — {{ $wrapped['busiestDay']['date'] }}</div>
                            @if(!empty($wrapped['busiestDay']['topTracks']))
                                <div class="psn-wrapped__record-detail">
                                    @foreach($wrapped['busiestDay']['topTracks'] as $t)
                                        <span>{{ $t['title'] }} ({{ $t['count'] }}×)</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($wrapped['mostRepeatedTrack'])
                    <div class="psn-wrapped__record">
                        <div class="psn-wrapped__record-icon">🔁</div>
                        <div>
                            <div class="psn-wrapped__record-value">{{ $wrapped['mostRepeatedTrack']['count'] }}× in one day</div>
                            <div class="psn-wrapped__record-label">Most repeated — {{ $wrapped['mostRepeatedTrack']['date'] }}</div>
                            <div class="psn-wrapped__record-detail">
                                <span>{{ $wrapped['mostRepeatedTrack']['title'] }}</span>
                                <span style="color:var(--color-text-muted)"> by {{ $wrapped['mostRepeatedTrack']['artist'] }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                @if($wrapped['longestStreak']['days'] > 0)
                    <div class="psn-wrapped__record">
                        <div class="psn-wrapped__record-icon">🔥</div>
                        <div>
                            <div class="psn-wrapped__record-value">{{ $wrapped['longestStreak']['days'] }}-day streak</div>
                            @if($wrapped['longestStreak']['startDate'] && $wrapped['longestStreak']['endDate'])
                                <div class="psn-wrapped__record-label">{{ $wrapped['longestStreak']['startDate'] }} – {{ $wrapped['longestStreak']['endDate'] }}</div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- ⑪ New discoveries --}}
        @if(!empty($wrapped['newDiscoveries']))
        <div class="psn-wrapped__section">
            <div class="psn-wrapped__section-title">New discoveries — {{ count($wrapped['newDiscoveries']) }} first-time tracks</div>
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                @foreach($wrapped['newDiscoveries'] as $track)
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        @if($track['image_url'])
                            <img src="{{ $track['image_url'] }}" alt="" style="width:2.25rem;height:2.25rem;border-radius:var(--radius-sm);object-fit:cover;flex-shrink:0;">
                        @else
                            <div style="width:2.25rem;height:2.25rem;border-radius:var(--radius-sm);background:var(--color-bg-tertiary);flex-shrink:0;"></div>
                        @endif
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.875rem;color:var(--color-text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $track['title'] }}</div>
                            <div style="font-size:0.75rem;color:var(--color-text-muted);">{{ $track['artist'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ⑫ First & last play --}}
        @if($wrapped['firstPlay'] || $wrapped['lastPlay'])
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <div class="psn-wrapped__section-title">First &amp; last play</div>
            <div class="psn-wrapped__boundary-sessions">

                @if($wrapped['firstPlay'])
                    <div class="psn-wrapped__boundary">
                        <div class="psn-wrapped__boundary-label">First play</div>
                        <div class="psn-wrapped__boundary-inner">
                            @if($wrapped['firstPlay']['image_url'])
                                <img src="{{ $wrapped['firstPlay']['image_url'] }}" alt="" class="psn-wrapped__boundary-art">
                            @else
                                <div class="psn-wrapped__boundary-art psn-wrapped__boundary-art--empty"></div>
                            @endif
                            <div>
                                <div class="psn-wrapped__boundary-game">{{ $wrapped['firstPlay']['title'] }}</div>
                                <div class="psn-wrapped__boundary-meta">{{ $wrapped['firstPlay']['artist'] }}</div>
                                <div class="psn-wrapped__boundary-meta">{{ $wrapped['firstPlay']['date'] }} at {{ $wrapped['firstPlay']['time'] }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($wrapped['lastPlay'])
                    <div class="psn-wrapped__boundary">
                        <div class="psn-wrapped__boundary-label">Last play</div>
                        <div class="psn-wrapped__boundary-inner">
                            @if($wrapped['lastPlay']['image_url'])
                                <img src="{{ $wrapped['lastPlay']['image_url'] }}" alt="" class="psn-wrapped__boundary-art">
                            @else
                                <div class="psn-wrapped__boundary-art psn-wrapped__boundary-art--empty"></div>
                            @endif
                            <div>
                                <div class="psn-wrapped__boundary-game">{{ $wrapped['lastPlay']['title'] }}</div>
                                <div class="psn-wrapped__boundary-meta">{{ $wrapped['lastPlay']['artist'] }}</div>
                                <div class="psn-wrapped__boundary-meta">{{ $wrapped['lastPlay']['date'] }} at {{ $wrapped['lastPlay']['time'] }}</div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
        @endif

        {{-- ⑬ Calendar heatmap --}}
        @if(!empty($wrapped['calendarHeatmap']))
        <div class="psn-wrapped__section psn-wrapped__section--full">
            <div class="psn-wrapped__section-title">Listening calendar</div>
            <div class="psn-monthly-heatmap">
                <div class="psn-monthly-heatmap__days-header">
                    @foreach(['M','T','W','T','F','S','S'] as $d)
                        <div class="psn-monthly-heatmap__day-label">{{ $d }}</div>
                    @endforeach
                </div>
                @foreach($wrapped['calendarHeatmap'] as $week)
                    <div class="psn-monthly-heatmap__week">
                        @foreach($week as $cell)
                            <div
                                class="psn-monthly-heatmap__cell {{ $cell['in_month'] ? 'psn-monthly-heatmap__cell--level-'.$cell['level'] : 'psn-monthly-heatmap__cell--outside' }}"
                                title="{{ $cell['in_month'] ? $cell['date'].': '.$cell['plays'].' play'.($cell['plays'] !== 1 ? 's' : '') : '' }}"
                            >
                                @if($cell['day'] !== null)
                                    <span class="psn-monthly-heatmap__cell-day">{{ $cell['day'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
                <div class="psn-monthly-heatmap__legend">
                    <span>Less</span>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--level-0"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--level-1"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--level-2"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--level-3"></div>
                    <div class="psn-monthly-heatmap__cell psn-monthly-heatmap__cell--level-4"></div>
                    <span>More</span>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /.psn-wrapped__grid --}}

    @endif{{-- hasData --}}

</div>{{-- /.psn-wrapped --}}

</x-layouts.app>
