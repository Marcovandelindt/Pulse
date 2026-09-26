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
    <div class="stats-row">
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
    </div>

    {{-- Main grid --}}
    <div class="dashboard-grid">
        <x-ui.card title="Recent Activity">
            <x-dashboard.activity-timeline :activities="$timeline" />
        </x-ui.card>

        <div class="flex flex-col gap-6">
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
