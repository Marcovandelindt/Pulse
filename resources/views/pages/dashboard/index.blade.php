<x-layouts.app title="Dashboard">

    <div x-data="{ syncOpen: false }" @keydown.escape.window="syncOpen = false">

    <x-layout.page-header
        title="{{ $greeting }}, {{ auth()->user()->name }}"
        :subtitle="$adaptiveSubtitle"
    >
        <x-slot:actions>
            <button @click="syncOpen = true" class="btn btn--secondary btn--sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync sessions
            </button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Milestone banner --}}
    @if($recentMilestone)
        <div class="milestone-banner">
            <span class="milestone-banner__icon">{{ $recentMilestone['icon'] }}</span>
            <div class="milestone-banner__text">
                <span class="milestone-banner__title">{{ $recentMilestone['title'] }}</span>
                <span class="milestone-banner__sub">{{ $recentMilestone['sub'] }}</span>
            </div>
            <span class="milestone-banner__sparkle">✨</span>
        </div>
    @endif

    {{-- Streak pills --}}
    @if($stepStreak > 0 || $sleepStreak > 0)
        <div class="dashboard-streaks">
            @if($stepStreak > 0)
                <div class="dashboard-streak-pill">
                    <span class="dashboard-streak-pill__icon">🔥</span>
                    <span class="dashboard-streak-pill__count">{{ $stepStreak }}</span>
                    <span class="dashboard-streak-pill__label">day{{ $stepStreak !== 1 ? 's' : '' }} step goal</span>
                </div>
            @endif
            @if($sleepStreak > 0)
                <div class="dashboard-streak-pill dashboard-streak-pill--sleep">
                    <span class="dashboard-streak-pill__icon">😴</span>
                    <span class="dashboard-streak-pill__count">{{ $sleepStreak }}</span>
                    <span class="dashboard-streak-pill__label">night{{ $sleepStreak !== 1 ? 's' : '' }} 7h+ sleep</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Daily brief --}}
    @if($dailyBrief['sleep'] || $dailyBrief['steps'])
        <div class="daily-brief">
            @if($dailyBrief['sleep'])
                <div class="daily-brief__section">
                    <span class="daily-brief__icon">😴</span>
                    <div class="daily-brief__content">
                        <span class="daily-brief__value">{{ $dailyBrief['sleep']['duration'] }}</span>
                        <span class="daily-brief__label">
                            last night
                            <span class="daily-brief__badge daily-brief__badge--{{ strtolower($dailyBrief['sleep']['label']) }}">{{ $dailyBrief['sleep']['label'] }}</span>
                        </span>
                    </div>
                </div>
            @endif

            @if($dailyBrief['sleep'] && $dailyBrief['steps'])
                <div class="daily-brief__divider"></div>
            @endif

            @if($dailyBrief['steps'])
                <div class="daily-brief__section">
                    <span class="daily-brief__icon">🚶</span>
                    <div class="daily-brief__content">
                        <span class="daily-brief__value">{{ $dailyBrief['steps'] }}</span>
                        <span class="daily-brief__label">
                            steps today
                            @if($dailyBrief['sevenDayAvg'])
                                · avg {{ $dailyBrief['sevenDayAvg'] }}
                                @if($dailyBrief['stepVsAvg'] !== null)
                                    <span class="daily-brief__delta {{ $dailyBrief['stepVsAvg'] >= 0 ? 'daily-brief__delta--up' : 'daily-brief__delta--down' }}">
                                        {{ $dailyBrief['stepVsAvg'] >= 0 ? '+' : '' }}{{ $dailyBrief['stepVsAvg'] }}%
                                    </span>
                                @endif
                            @endif
                        </span>
                    </div>
                </div>
            @endif

            @if($dailyBrief['goalPct'] !== null && $dailyBrief['goalPct'] < 100)
                <div class="daily-brief__divider"></div>
                <div class="daily-brief__section daily-brief__section--goal">
                    <div class="daily-brief__goal-bar-wrap">
                        <div class="daily-brief__goal-bar" style="width: {{ $dailyBrief['goalPct'] }}%"></div>
                    </div>
                    <span class="daily-brief__label">
                        {{ $dailyBrief['goalPct'] }}% of goal
                        @if($dailyBrief['remaining'])
                            · {{ $dailyBrief['remaining'] }} to go
                        @endif
                    </span>
                </div>
            @elseif($dailyBrief['goalPct'] === 100)
                <div class="daily-brief__divider"></div>
                <div class="daily-brief__section">
                    <span class="daily-brief__icon">✅</span>
                    <span class="daily-brief__label daily-brief__label--success">Step goal reached!</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Stats row --}}
    <div class="stats-row stats-row--five">
        <x-stats.stat-card
            label="Steps this week"
            :value="$stepsThisWeek ?? '—'"
            icon="heart"
        />
        <x-stats.stat-card
            label="Watchtime this week"
            :value="$watchtimeThisWeek ?? '—'"
            icon="film"
        />
        <x-stats.stat-card
            label="PlayStation this week"
            :value="$playtimeThisWeek ?? '—'"
            icon="gamepad"
        />
        <x-stats.stat-card
            label="Tracks this week"
            :value="$tracksThisWeek ?? '—'"
            icon="musical-note"
        />
        <x-stats.stat-card
            label="Spending this week"
            :value="$spendingThisWeek ?? '—'"
            icon="credit-card"
        />
    </div>

    {{-- Main grid --}}
    <div class="dashboard-grid">
        <x-ui.card title="Recent Activity">
            <x-dashboard.activity-timeline :activities="$timeline" />
        </x-ui.card>

        <div class="flex flex-col gap-6">

            {{-- Weather widget --}}
            @if($weather)
            <div class="weather-widget"
                 data-condition="{{ $weather['condition_class'] }}"
                 @if(!$weather['is_day']) data-night @endif>
                <div class="wx-bg" aria-hidden="true">
                    @switch($weather['condition_class'])
                        @case('clear')
                            @if($weather['is_day'])
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                    <circle cx="170" cy="30" r="42" opacity="0.55"/>
                                    <circle cx="170" cy="30" r="60" fill="none" stroke="currentColor" stroke-width="2" opacity="0.25"/>
                                    <circle cx="170" cy="30" r="78" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.12"/>
                                    <g stroke="currentColor" stroke-linecap="round" stroke-width="3" opacity="0.3" fill="none">
                                        <line x1="170" y1="82" x2="170" y2="98"/>
                                        <line x1="124" y1="76" x2="112" y2="88"/>
                                        <line x1="106" y1="30" x2="90" y2="30"/>
                                        <line x1="124" y1="-16" x2="112" y2="-28"/>
                                        <line x1="216" y1="-16" x2="228" y2="-28"/>
                                        <line x1="234" y1="30" x2="250" y2="30"/>
                                        <line x1="216" y1="76" x2="228" y2="88"/>
                                    </g>
                                </svg>
                            @else
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                    <path fill-rule="evenodd" opacity="0.6" d="M 125,40 a 30,30,0,1,0,60,0 a 30,30,0,1,0,-60,0 Z M 141,32 a 20,20,0,1,0,40,0 a 20,20,0,1,0,-40,0 Z"/>
                                    <circle cx="65" cy="22" r="2.5" opacity="0.65"/>
                                    <circle cx="40" cy="58" r="1.5" opacity="0.5"/>
                                    <circle cx="100" cy="72" r="1.5" opacity="0.45"/>
                                    <circle cx="20" cy="92" r="2" opacity="0.6"/>
                                    <circle cx="78" cy="115" r="1" opacity="0.4"/>
                                    <circle cx="50" cy="148" r="1.5" opacity="0.5"/>
                                    <g stroke="currentColor" stroke-linecap="round" stroke-width="1" fill="none" opacity="0.45">
                                        <line x1="65" y1="17" x2="65" y2="27"/>
                                        <line x1="60" y1="22" x2="70" y2="22"/>
                                    </g>
                                </svg>
                            @endif
                            @break
                        @case('partly-cloudy')
                            @if($weather['is_day'])
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                    <circle cx="145" cy="52" r="26" opacity="0.28"/>
                                    <g stroke="currentColor" stroke-linecap="round" stroke-width="2.5" fill="none" opacity="0.2">
                                        <line x1="145" y1="85" x2="145" y2="97"/>
                                        <line x1="118" y1="79" x2="109" y2="88"/>
                                        <line x1="106" y1="52" x2="94" y2="52"/>
                                        <line x1="174" y1="79" x2="183" y2="88"/>
                                        <line x1="184" y1="52" x2="196" y2="52"/>
                                        <line x1="174" y1="25" x2="183" y2="16"/>
                                    </g>
                                    <path d="M55,145 Q45,122 62,112 Q67,95 88,99 Q97,84 117,90 Q130,77 148,85 Q165,80 168,96 Q183,102 180,120 Q176,133 162,131 L70,131 Q52,128 55,145 Z" opacity="0.5"/>
                                </svg>
                            @else
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                    <path d="M55,145 Q45,122 62,112 Q67,95 88,99 Q97,84 117,90 Q130,77 148,85 Q165,80 168,96 Q183,102 180,120 Q176,133 162,131 L70,131 Q52,128 55,145 Z" opacity="0.45"/>
                                    <circle cx="35" cy="30" r="2" opacity="0.55"/>
                                    <circle cx="65" cy="15" r="1.5" opacity="0.45"/>
                                    <circle cx="20" cy="70" r="1.5" opacity="0.5"/>
                                    <circle cx="55" cy="168" r="1" opacity="0.35"/>
                                </svg>
                            @endif
                            @break
                        @case('cloudy')
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                <path d="M28,165 Q12,143 28,130 Q33,110 56,115 Q64,98 85,104 Q95,88 116,95 Q138,85 151,101 Q170,97 173,115 Q193,123 189,142 Q185,155 168,153 L42,153 Q24,150 28,165 Z" opacity="0.42"/>
                                <path d="M88,110 Q80,94 92,85 Q97,72 113,77 Q121,64 138,70 Q154,64 160,76 Q173,74 175,86 Q188,92 185,106 Q181,116 168,114 L98,114 Q84,112 88,110 Z" opacity="0.32"/>
                            </svg>
                            @break
                        @case('fog')
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="none" stroke-linecap="round">
                                <path d="M10,50 Q45,44 80,50 Q115,56 150,50 Q175,44 205,50" stroke-width="3" opacity="0.5"/>
                                <path d="M20,78 Q55,72 90,78 Q125,84 160,78 Q182,72 205,78" stroke-width="2.5" opacity="0.42"/>
                                <path d="M5,106 Q40,100 75,106 Q110,112 145,106 Q172,100 205,106" stroke-width="3" opacity="0.45"/>
                                <path d="M15,134 Q50,128 85,134 Q120,140 155,134 Q177,128 205,134" stroke-width="2" opacity="0.35"/>
                                <path d="M25,162 Q60,156 95,162 Q130,168 165,162 Q184,156 205,162" stroke-width="2.5" opacity="0.28"/>
                            </svg>
                            @break
                        @case('drizzle')
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="none" stroke-linecap="round">
                                <path d="M68,32 Q58,14 73,5 Q80,-8 98,-3 Q106,-16 123,-8 Q140,-16 148,-2 Q163,-4 165,10 Q180,16 176,30 Q172,42 158,41 L80,41 Q63,38 68,32 Z" fill="currentColor" opacity="0.3"/>
                                <g stroke-width="1.5" opacity="0.4">
                                    <line x1="68" y1="58" x2="62" y2="78"/>
                                    <line x1="94" y1="54" x2="88" y2="74"/>
                                    <line x1="120" y1="58" x2="114" y2="78"/>
                                    <line x1="146" y1="54" x2="140" y2="74"/>
                                    <line x1="172" y1="58" x2="166" y2="78"/>
                                    <line x1="80" y1="94" x2="74" y2="114"/>
                                    <line x1="106" y1="90" x2="100" y2="110"/>
                                    <line x1="132" y1="94" x2="126" y2="114"/>
                                    <line x1="158" y1="90" x2="152" y2="110"/>
                                    <line x1="88" y1="130" x2="82" y2="150"/>
                                    <line x1="114" y1="126" x2="108" y2="146"/>
                                    <line x1="140" y1="130" x2="134" y2="150"/>
                                    <line x1="166" y1="126" x2="160" y2="146"/>
                                </g>
                            </svg>
                            @break
                        @case('rain')
                        @case('showers')
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="none" stroke-linecap="round">
                                <path d="M65,28 Q55,10 70,2 Q77,-11 95,-6 Q103,-19 121,-11 Q138,-19 146,-5 Q161,-7 163,7 Q178,13 174,27 Q170,39 156,38 L78,38 Q61,35 65,28 Z" fill="currentColor" opacity="0.35"/>
                                <g stroke-width="2" opacity="0.45">
                                    <line x1="62" y1="58" x2="52" y2="90"/>
                                    <line x1="88" y1="53" x2="78" y2="85"/>
                                    <line x1="114" y1="58" x2="104" y2="90"/>
                                    <line x1="140" y1="53" x2="130" y2="85"/>
                                    <line x1="166" y1="58" x2="156" y2="90"/>
                                    <line x1="74" y1="100" x2="64" y2="132"/>
                                    <line x1="100" y1="95" x2="90" y2="127"/>
                                    <line x1="126" y1="100" x2="116" y2="132"/>
                                    <line x1="152" y1="95" x2="142" y2="127"/>
                                    <line x1="86" y1="142" x2="76" y2="174"/>
                                    <line x1="112" y1="137" x2="102" y2="169"/>
                                    <line x1="138" y1="142" x2="128" y2="174"/>
                                </g>
                            </svg>
                            @break
                        @case('snow')
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="none" stroke-linecap="round">
                                <path d="M72,28 Q62,10 77,2 Q84,-11 102,-6 Q110,-19 128,-11 Q145,-19 153,-5 Q168,-7 170,7 Q185,13 181,27 Q177,39 163,38 L85,38 Q68,35 72,28 Z" fill="currentColor" opacity="0.3"/>
                                <g stroke-width="2" opacity="0.45" transform="translate(72, 75)">
                                    <line x1="0" y1="-12" x2="0" y2="12"/><line x1="-12" y1="0" x2="12" y2="0"/>
                                    <line x1="-8.5" y1="-8.5" x2="8.5" y2="8.5"/><line x1="8.5" y1="-8.5" x2="-8.5" y2="8.5"/>
                                </g>
                                <g stroke-width="2" opacity="0.4" transform="translate(136, 75)">
                                    <line x1="0" y1="-10" x2="0" y2="10"/><line x1="-10" y1="0" x2="10" y2="0"/>
                                    <line x1="-7" y1="-7" x2="7" y2="7"/><line x1="7" y1="-7" x2="-7" y2="7"/>
                                </g>
                                <g stroke-width="1.5" opacity="0.38" transform="translate(104, 130)">
                                    <line x1="0" y1="-9" x2="0" y2="9"/><line x1="-9" y1="0" x2="9" y2="0"/>
                                    <line x1="-6" y1="-6" x2="6" y2="6"/><line x1="6" y1="-6" x2="-6" y2="6"/>
                                </g>
                                <g stroke-width="1.5" opacity="0.32" transform="translate(58, 152)">
                                    <line x1="0" y1="-8" x2="0" y2="8"/><line x1="-8" y1="0" x2="8" y2="0"/>
                                    <line x1="-5.5" y1="-5.5" x2="5.5" y2="5.5"/><line x1="5.5" y1="-5.5" x2="-5.5" y2="5.5"/>
                                </g>
                                <g stroke-width="1.5" opacity="0.32" transform="translate(158, 148)">
                                    <line x1="0" y1="-8" x2="0" y2="8"/><line x1="-8" y1="0" x2="8" y2="0"/>
                                    <line x1="-5.5" y1="-5.5" x2="5.5" y2="5.5"/><line x1="5.5" y1="-5.5" x2="-5.5" y2="5.5"/>
                                </g>
                            </svg>
                            @break
                        @case('thunder')
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                <path d="M52,88 Q35,62 52,50 Q57,30 80,35 Q88,17 108,24 Q130,12 143,28 Q163,24 166,43 Q186,51 182,72 Q178,88 160,86 L65,86 Q47,83 52,88 Z" opacity="0.45"/>
                                <path d="M 112,92 L 96,133 L 108,133 L 84,178 L 128,122 L 114,122 L 132,92 Z" opacity="0.65"/>
                            </svg>
                            @break
                        @default
                            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                <circle cx="170" cy="30" r="42" opacity="0.55"/>
                                <circle cx="170" cy="30" r="60" fill="none" stroke="currentColor" stroke-width="2" opacity="0.25"/>
                            </svg>
                    @endswitch
                </div>
                <div class="wx-body">
                    <div class="wx-location">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        {{ $weather['city'] }}
                    </div>
                    <div class="wx-temp">{{ $weather['temperature'] }}<span class="wx-unit">°C</span></div>
                    <div class="wx-condition">{{ $weather['condition'] }}</div>
                    <div class="wx-meta">
                        <div class="wx-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>
                            </svg>
                            <span>Feels {{ $weather['apparent_temperature'] }}°</span>
                        </div>
                        <div class="wx-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                            </svg>
                            <span>{{ $weather['humidity'] }}%</span>
                        </div>
                        <div class="wx-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/>
                            </svg>
                            <span>{{ $weather['wind_speed'] }} km/h</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <x-ui.card title="Now listening" class="card--flush">
                @if($currentlyPlaying)
                    <div class="now-listening now-listening--playing">
                        @if($currentlyPlaying['album_image_url'])
                            <div class="now-listening__cover-wrap">
                                <img src="{{ $currentlyPlaying['album_image_url'] }}" alt="">
                            </div>
                        @endif
                        <div class="now-listening__info">
                            <div class="now-listening__track">
                                <a href="{{ route('music.tracks.show', $currentlyPlaying['track']) }}" style="color:inherit;" class="hover:underline">{{ $currentlyPlaying['track_name'] }}</a>
                            </div>
                            <div class="now-listening__artist">
                                @foreach($currentlyPlaying['artists'] as $artist)
                                    @php $artistModel = $currentlyPlaying['artist_models'][$artist['spotify_artist_id']] ?? null @endphp
                                    @if($artistModel)
                                        <a href="{{ route('music.artists.show', $artistModel) }}" style="color:inherit;" class="hover:underline">{{ $artist['name'] }}</a>
                                    @else
                                        {{ $artist['name'] }}
                                    @endif
                                    @if(!$loop->last)<span> · </span>@endif
                                @endforeach
                            </div>
                            <div class="now-listening__meta">
                                <div class="now-listening__bars">
                                    <div class="now-listening__bar"></div>
                                    <div class="now-listening__bar"></div>
                                    <div class="now-listening__bar"></div>
                                    <div class="now-listening__bar"></div>
                                </div>
                                <span class="now-listening__label">Now playing</span>
                            </div>
                        </div>
                    </div>
                @elseif($recentPlay)
                    <div class="now-listening">
                        @if($recentPlay->track->album?->image_url)
                            <div class="now-listening__cover-wrap">
                                <img src="{{ $recentPlay->track->album->image_url }}" alt="">
                            </div>
                        @endif
                        <div class="now-listening__info">
                            <div class="now-listening__track">
                                <a href="{{ route('music.tracks.show', $recentPlay->track) }}" style="color:inherit;" class="hover:underline">{{ $recentPlay->track->title }}</a>
                            </div>
                            <div class="now-listening__artist">
                                @foreach($recentPlay->track->artists as $artist)
                                    <a href="{{ route('music.artists.show', $artist) }}" style="color:inherit;" class="hover:underline">{{ $artist->name }}</a>
                                    @if(!$loop->last)<span> · </span>@endif
                                @endforeach
                            </div>
                            <div class="now-listening__meta">
                                <span class="now-listening__label">{{ $recentPlay->played_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <x-ui.empty-state title="Nothing playing" />
                @endif
            </x-ui.card>

            @if($currentGame)
                <x-ui.card title="Now gaming" class="card--flush">
                    <x-playstation.now-gaming :game="$currentGame" :startedAt="$gamingStartedAt" />
                </x-ui.card>
            @elseif($lastPlayedGame)
                <x-ui.card title="Last played" class="card--flush">
                    <x-playstation.now-gaming :game="$lastPlayedGame" :playing="false" :playedAt="$lastPlayedAt" :url="$lastPlayedGameUrl" />
                </x-ui.card>
            @endif

            @if($weekTopArtist)
                <x-ui.card title="Top artist this week" class="card--flush">
                    <div class="top-artist">
                        @if($weekTopArtist['artist']->image_url)
                            <div class="top-artist__avatar">
                                <img src="{{ $weekTopArtist['artist']->image_url }}" alt="{{ $weekTopArtist['artist']->name }}">
                            </div>
                        @else
                            <div class="top-artist__avatar top-artist__avatar--placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="color:var(--color-text-muted)">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                </svg>
                            </div>
                        @endif
                        <div class="top-artist__info">
                            <a href="{{ route('music.artists.show', $weekTopArtist['artist']) }}"
                               class="top-artist__name hover:underline">{{ $weekTopArtist['artist']->name }}</a>
                            <div class="top-artist__meta">
                                <span class="top-artist__plays">{{ $weekTopArtist['playCount'] }} plays</span>
                                @if($weekTopArtist['topGenre'])
                                    <span class="top-artist__sep">·</span>
                                    <span class="top-artist__genre">{{ $weekTopArtist['topGenre'] }}</span>
                                @endif
                            </div>
                            <div class="top-artist__bar-wrap">
                                <div class="top-artist__bar" style="width: {{ $weekTopArtist['totalTracks'] > 0 ? min(100, round($weekTopArtist['playCount'] / $weekTopArtist['totalTracks'] * 100)) : 0 }}%"></div>
                            </div>
                            <span class="top-artist__share">
                                {{ $weekTopArtist['totalTracks'] > 0 ? round($weekTopArtist['playCount'] / $weekTopArtist['totalTracks'] * 100) : 0 }}% of this week's plays
                            </span>
                        </div>
                    </div>
                </x-ui.card>
            @endif

            @if(!empty($personalRecords))
                <x-ui.card>
                    <div class="personal-records">
                        <div class="personal-records__heading">
                            <span class="personal-records__icon">🏅</span>
                            <span class="personal-records__title">Personal records</span>
                        </div>
                        <div class="personal-records__list">
                            @foreach($personalRecords as $record)
                                <div class="personal-records__item">
                                    <span class="personal-records__item-icon">{{ $record['icon'] }}</span>
                                    <div class="personal-records__item-body">
                                        <span class="personal-records__item-label">{{ $record['label'] }}</span>
                                        <span class="personal-records__item-value">{{ $record['value'] }}</span>
                                    </div>
                                    <span class="personal-records__item-date">{{ $record['date'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>
            @endif

            @if($lastYear)
                <x-ui.card
                    x-data="{ panel: null }"
                    @keydown.escape.window="panel = null"
                >
                    <div class="last-year">
                        <div class="last-year__heading">
                            <span class="last-year__icon">📅</span>
                            <span class="last-year__title">This time last year</span>
                            <a href="{{ route('day.show', $lastYear['date']->format('Y-m-d')) }}" class="last-year__date last-year__date--link">{{ $lastYear['date']->format('M j, Y') }}</a>
                        </div>
                        <div class="last-year__items">
                            @if($lastYear['steps'])
                                <div class="last-year__item">
                                    <span class="last-year__item-icon">🚶</span>
                                    <span class="last-year__item-value">{{ $lastYear['steps'] }}</span>
                                    <span class="last-year__item-label">steps</span>
                                </div>
                            @endif
                            @if($lastYear['sleep'])
                                <div class="last-year__item">
                                    <span class="last-year__item-icon">😴</span>
                                    <span class="last-year__item-value">{{ $lastYear['sleep'] }}</span>
                                    <span class="last-year__item-label">sleep</span>
                                </div>
                            @endif
                            @if($lastYear['tracks'])
                                <button class="last-year__item last-year__item--clickable" @click="panel = 'tracks'">
                                    <span class="last-year__item-icon">🎵</span>
                                    <span class="last-year__item-value">{{ $lastYear['tracks'] }}</span>
                                    <span class="last-year__item-label">track{{ $lastYear['tracks'] !== 1 ? 's' : '' }}</span>
                                    <span class="last-year__item-hint">tap</span>
                                </button>
                            @endif
                            @if($lastYear['gaming'])
                                <button class="last-year__item last-year__item--clickable" @click="panel = 'gaming'">
                                    <span class="last-year__item-icon">🎮</span>
                                    <span class="last-year__item-value">{{ $lastYear['gaming'] }}</span>
                                    <span class="last-year__item-label">gaming</span>
                                    <span class="last-year__item-hint">tap</span>
                                </button>
                            @endif
                            @if($lastYear['episodes'])
                                <button class="last-year__item last-year__item--clickable" @click="panel = 'episodes'">
                                    <span class="last-year__item-icon">📺</span>
                                    <span class="last-year__item-value">{{ $lastYear['episodes'] }}</span>
                                    <span class="last-year__item-label">episode{{ $lastYear['episodes'] !== 1 ? 's' : '' }}</span>
                                    <span class="last-year__item-hint">tap</span>
                                </button>
                            @endif
                            @if($lastYear['movieWatched'])
                                <a href="{{ $lastYear['movieUrl'] }}" class="last-year__item last-year__item--clickable">
                                    <span class="last-year__item-icon">🎬</span>
                                    <span class="last-year__item-value last-year__item-value--sm">{{ Str::limit($lastYear['movieTitle'] ?? 'Movie', 12) }}</span>
                                    <span class="last-year__item-label">movie</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Detail panels --}}
                    <template x-if="panel !== null">
                        <div class="last-year-panel" @click.self="panel = null">
                            <div class="last-year-panel__inner">

                                {{-- Tracks panel --}}
                                <template x-if="panel === 'tracks'">
                                    <div>
                                        <div class="last-year-panel__header">
                                            <span>🎵 Tracks — {{ $lastYear['date']->format('M j, Y') }}</span>
                                            <button class="last-year-panel__close" @click="panel = null">✕</button>
                                        </div>
                                        <div class="last-year-panel__list">
                                            @foreach($lastYear['trackList'] as $t)
                                                <a href="{{ $t['url'] }}" class="last-year-panel__row">
                                                    <span class="last-year-panel__time">{{ $t['time'] }}</span>
                                                    <div class="last-year-panel__row-body">
                                                        <span class="last-year-panel__primary">{{ $t['title'] }}</span>
                                                        <span class="last-year-panel__secondary">{{ $t['artist'] }}</span>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </template>

                                {{-- Gaming panel --}}
                                <template x-if="panel === 'gaming'">
                                    <div>
                                        <div class="last-year-panel__header">
                                            <span>🎮 Gaming — {{ $lastYear['date']->format('M j, Y') }}</span>
                                            <button class="last-year-panel__close" @click="panel = null">✕</button>
                                        </div>
                                        <div class="last-year-panel__list">
                                            @foreach($lastYear['sessions'] as $s)
                                                <a href="{{ $s['url'] }}" class="last-year-panel__row last-year-panel__row--game">
                                                    @if($s['imageUrl'])
                                                        <img src="{{ $s['imageUrl'] }}" alt="" class="last-year-panel__game-cover">
                                                    @else
                                                        <div class="last-year-panel__game-cover last-year-panel__game-cover--placeholder">🎮</div>
                                                    @endif
                                                    <div class="last-year-panel__row-body">
                                                        <span class="last-year-panel__primary">{{ $s['game'] }}</span>
                                                        <span class="last-year-panel__secondary">{{ $s['start'] }} – {{ $s['end'] }} · {{ $s['duration'] }}</span>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </template>

                                {{-- Episodes panel --}}
                                <template x-if="panel === 'episodes'">
                                    <div>
                                        <div class="last-year-panel__header">
                                            <span>📺 Episodes — {{ $lastYear['date']->format('M j, Y') }}</span>
                                            <button class="last-year-panel__close" @click="panel = null">✕</button>
                                        </div>
                                        <div class="last-year-panel__list">
                                            @foreach($lastYear['episodeList'] as $e)
                                                <a href="{{ $e['url'] }}" class="last-year-panel__row">
                                                    <div class="last-year-panel__row-body">
                                                        <span class="last-year-panel__primary">{{ $e['series'] }}</span>
                                                        <span class="last-year-panel__secondary">{{ $e['episode'] }}</span>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </template>

                            </div>
                        </div>
                    </template>
                </x-ui.card>
            @endif
        </div>
    </div>

    <x-layout.notification />

    {{-- PlayStation session sync modal --}}
    <div x-show="syncOpen" x-cloak class="psn-sync-modal" @click.self="syncOpen = false">
        <div class="psn-sync-modal__panel">
            <div class="psn-sync-modal__header">
                <span>Sync PlayStation Sessions</span>
                <button class="psn-sync-modal__close" @click="syncOpen = false">✕</button>
            </div>

            <form method="POST" action="{{ route('playstation.sessions.sync') }}" class="psn-sync-modal__body">
                @csrf
                <p class="psn-sync-modal__desc">
                    Pulls recent sessions from ps-timetracker.com. If your profile is private, paste your session cookie below.
                </p>

                <label class="psn-sync-modal__label" for="sync-cookie">
                    Session cookie
                    <span class="psn-sync-modal__optional">optional</span>
                </label>
                <textarea
                    id="sync-cookie"
                    name="cookie"
                    class="psn-sync-modal__textarea"
                    rows="3"
                    placeholder="_my_app_session=..."
                    spellcheck="false"
                ></textarea>
                <p class="psn-sync-modal__hint">
                    Find it in DevTools → Application → Cookies → ps-timetracker.com → <code>_my_app_session</code>
                </p>

                <div class="psn-sync-modal__footer">
                    <button type="button" class="btn btn--secondary btn--sm" @click="syncOpen = false">Cancel</button>
                    <button type="submit" class="btn btn--primary btn--sm">Sync now</button>
                </div>
            </form>
        </div>
    </div>

    </div>{{-- end x-data="{ syncOpen }" --}}

</x-layouts.app>
