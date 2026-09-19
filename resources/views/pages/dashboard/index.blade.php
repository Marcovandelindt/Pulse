<x-layouts.app title="Dashboard">

    <x-layout.page-header
        title="{{ $greeting }}, {{ auth()->user()->name }}"
        :subtitle="$adaptiveSubtitle"
    />

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

            @if($lastYear)
                <x-ui.card>
                    <div class="last-year">
                        <div class="last-year__heading">
                            <span class="last-year__icon">📅</span>
                            <span class="last-year__title">This time last year</span>
                            <span class="last-year__date">{{ $lastYear['date']->format('M j, Y') }}</span>
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
                                <div class="last-year__item">
                                    <span class="last-year__item-icon">🎵</span>
                                    <span class="last-year__item-value">{{ $lastYear['tracks'] }}</span>
                                    <span class="last-year__item-label">track{{ $lastYear['tracks'] !== 1 ? 's' : '' }}</span>
                                </div>
                            @endif
                            @if($lastYear['gaming'])
                                <div class="last-year__item">
                                    <span class="last-year__item-icon">🎮</span>
                                    <span class="last-year__item-value">{{ $lastYear['gaming'] }}</span>
                                    <span class="last-year__item-label">gaming</span>
                                </div>
                            @endif
                            @if($lastYear['episodes'])
                                <div class="last-year__item">
                                    <span class="last-year__item-icon">📺</span>
                                    <span class="last-year__item-value">{{ $lastYear['episodes'] }}</span>
                                    <span class="last-year__item-label">episode{{ $lastYear['episodes'] !== 1 ? 's' : '' }}</span>
                                </div>
                            @endif
                            @if($lastYear['movieWatched'])
                                <div class="last-year__item">
                                    <span class="last-year__item-icon">🎬</span>
                                    <span class="last-year__item-value">1</span>
                                    <span class="last-year__item-label">movie</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endif
        </div>
    </div>

    <x-layout.notification />

</x-layouts.app>
