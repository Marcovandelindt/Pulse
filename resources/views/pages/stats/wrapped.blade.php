<x-layouts.app :title="$wrapped['year'] . ' Wrapped'">

    <x-layout.page-header :title="$wrapped['year'] . ' Wrapped'">
        <x-slot:actions>
            @if (count($availableYears) > 1)
                <div style="display: flex; gap: 0.375rem; flex-wrap: wrap;">
                    @foreach ($availableYears as $y)
                        <a href="{{ route('stats.wrapped', ['year' => $y]) }}"
                           class="btn btn--sm {{ $y === $wrapped['year'] ? 'btn--primary' : 'btn--secondary' }}">
                            {{ $y }}
                        </a>
                    @endforeach
                </div>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    <div class="wrapped">

        {{-- ── Music ──────────────────────────────────────────────────── --}}
        @if ($wrapped['music']['total_plays'] > 0)
            <div class="wrapped__chapter wrapped__chapter--music" x-data="{ open: true }">
                <button class="wrapped__chapter-header" @click="open = !open" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" class="wrapped__chapter-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                    </svg>
                    <span class="wrapped__chapter-title">Music</span>
                    <svg class="wrapped__chapter-chevron" :class="{ 'wrapped__chapter-chevron--open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="wrapped__chapter-body" x-show="open" x-transition>

                    <div class="wrapped__stats-row">
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['music']['total_plays']) }}</div>
                            <div class="wrapped__stat-label">Tracks played</div>
                        </div>
                        @if ($wrapped['music']['minutes_listened'] > 0)
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ number_format(intdiv($wrapped['music']['minutes_listened'], 60)) }}</div>
                                <div class="wrapped__stat-label">Hours listened</div>
                            </div>
                        @endif
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['music']['unique_tracks']) }}</div>
                            <div class="wrapped__stat-label">Unique tracks</div>
                        </div>
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['music']['unique_artists']) }}</div>
                            <div class="wrapped__stat-label">Unique artists</div>
                        </div>
                        @if ($wrapped['music']['best_month'])
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ $wrapped['music']['best_month']['label'] }}</div>
                                <div class="wrapped__stat-label">Most active month</div>
                            </div>
                        @endif
                    </div>

                    <div class="wrapped__lists">
                        @if (count($wrapped['music']['top_artists']) > 0)
                            <div class="wrapped__list">
                                <div class="wrapped__list-title">Top Artists</div>
                                @php $maxArtistPlays = $wrapped['music']['top_artists'][0]['plays']; @endphp
                                @foreach ($wrapped['music']['top_artists'] as $i => $artist)
                                    <a href="{{ route('music.artists.show', $artist['id']) }}" class="wrapped__rank-item">
                                        <div class="wrapped__rank-num">{{ $i + 1 }}</div>
                                        @if ($artist['image_url'])
                                            <img src="{{ $artist['image_url'] }}" alt="{{ $artist['name'] }}" class="wrapped__rank-thumb wrapped__rank-thumb--circle">
                                        @else
                                            <div class="wrapped__rank-thumb wrapped__rank-thumb--circle wrapped__rank-thumb--empty wrapped__rank-thumb--music"></div>
                                        @endif
                                        <div class="wrapped__rank-info">
                                            <div class="wrapped__rank-name">{{ $artist['name'] }}</div>
                                            <div class="wrapped__rank-bar-wrap">
                                                <div class="wrapped__rank-bar wrapped__rank-bar--music" style="width: {{ round($artist['plays'] / $maxArtistPlays * 100) }}%"></div>
                                            </div>
                                        </div>
                                        <div class="wrapped__rank-count">{{ number_format($artist['plays']) }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if (count($wrapped['music']['top_tracks']) > 0)
                            <div class="wrapped__list">
                                <div class="wrapped__list-title">Top Tracks</div>
                                @php $maxTrackPlays = $wrapped['music']['top_tracks'][0]['plays']; @endphp
                                @foreach ($wrapped['music']['top_tracks'] as $i => $track)
                                    <a href="{{ route('music.tracks.show', $track['id']) }}" class="wrapped__rank-item">
                                        <div class="wrapped__rank-num">{{ $i + 1 }}</div>
                                        <div class="wrapped__rank-info">
                                            <div class="wrapped__rank-name">{{ $track['title'] }}</div>
                                            <div class="wrapped__rank-sub">{{ $track['artist'] }}</div>
                                            <div class="wrapped__rank-bar-wrap">
                                                <div class="wrapped__rank-bar wrapped__rank-bar--music" style="width: {{ round($track['plays'] / $maxTrackPlays * 100) }}%"></div>
                                            </div>
                                        </div>
                                        <div class="wrapped__rank-count">{{ number_format($track['plays']) }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if (count($wrapped['music']['top_albums']) > 0)
                            <div class="wrapped__list">
                                <div class="wrapped__list-title">Top Albums</div>
                                @php $maxAlbumPlays = $wrapped['music']['top_albums'][0]['plays']; @endphp
                                @foreach ($wrapped['music']['top_albums'] as $i => $album)
                                    <a href="{{ route('music.albums.show', $album['id']) }}" class="wrapped__rank-item">
                                        <div class="wrapped__rank-num">{{ $i + 1 }}</div>
                                        @if ($album['image_url'])
                                            <img src="{{ $album['image_url'] }}" alt="{{ $album['name'] }}" class="wrapped__rank-thumb">
                                        @else
                                            <div class="wrapped__rank-thumb wrapped__rank-thumb--empty wrapped__rank-thumb--music"></div>
                                        @endif
                                        <div class="wrapped__rank-info">
                                            <div class="wrapped__rank-name">{{ $album['name'] }}</div>
                                            <div class="wrapped__rank-bar-wrap">
                                                <div class="wrapped__rank-bar wrapped__rank-bar--music" style="width: {{ round($album['plays'] / $maxAlbumPlays * 100) }}%"></div>
                                            </div>
                                        </div>
                                        <div class="wrapped__rank-count">{{ number_format($album['plays']) }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @endif

        {{-- ── Gaming ──────────────────────────────────────────────────── --}}
        @if ($wrapped['gaming']['total_minutes'] > 0)
            @php
                $gamingHours = intdiv($wrapped['gaming']['total_minutes'], 60);
                $gamingMins  = $wrapped['gaming']['total_minutes'] % 60;
                $gamingTime  = $gamingMins > 0 ? "{$gamingHours}h {$gamingMins}m" : "{$gamingHours}h";
            @endphp
            <div class="wrapped__chapter wrapped__chapter--gaming" x-data="{ open: true }">
                <button class="wrapped__chapter-header" @click="open = !open" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" class="wrapped__chapter-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8a3 3 0 013-3h8a3 3 0 013 3v8a3 3 0 01-3 3H8a3 3 0 01-3-3V8zM9 10v4M7 12h4M15 10h.01M15 14h.01M13 12h.01M17 12h.01" />
                    </svg>
                    <span class="wrapped__chapter-title">Gaming</span>
                    <svg class="wrapped__chapter-chevron" :class="{ 'wrapped__chapter-chevron--open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="wrapped__chapter-body" x-show="open" x-transition>

                    <div class="wrapped__stats-row">
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ $gamingTime }}</div>
                            <div class="wrapped__stat-label">Time played</div>
                        </div>
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['gaming']['sessions']) }}</div>
                            <div class="wrapped__stat-label">Sessions</div>
                        </div>
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ $wrapped['gaming']['games_played'] }}</div>
                            <div class="wrapped__stat-label">Games played</div>
                        </div>
                        @if ($wrapped['gaming']['longest_session'])
                            @php
                                $ls = $wrapped['gaming']['longest_session'];
                                $lsh = intdiv($ls, 60); $lsm = $ls % 60;
                                $lst = $lsm > 0 ? "{$lsh}h {$lsm}m" : "{$lsh}h";
                            @endphp
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ $lst }}</div>
                                <div class="wrapped__stat-label">Longest session</div>
                            </div>
                        @endif
                        @if ($wrapped['gaming']['best_month'])
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ $wrapped['gaming']['best_month']['label'] }}</div>
                                <div class="wrapped__stat-label">Most active month</div>
                            </div>
                        @endif
                    </div>

                    @if ($wrapped['gaming']['trophies_earned'] > 0)
                        <div class="wrapped__trophy-row">
                            @foreach (['platinum' => '#8b7cf8', 'gold' => '#c9a227', 'silver' => '#9ea3a8', 'bronze' => '#b36a2a'] as $type => $color)
                                @if (($wrapped['gaming']['trophies_by_type'][$type] ?? 0) > 0)
                                    <div class="wrapped__trophy-badge">
                                        <span class="wrapped__trophy-dot" style="background: {{ $color }}"></span>
                                        <span class="wrapped__trophy-count">{{ $wrapped['gaming']['trophies_by_type'][$type] }}</span>
                                        <span class="wrapped__trophy-type">{{ ucfirst($type) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if (count($wrapped['gaming']['top_games']) > 0)
                        <div class="wrapped__list" style="margin-top: 1.25rem;">
                            <div class="wrapped__list-title">Most Played Games</div>
                            @php $maxGameMins = $wrapped['gaming']['top_games'][0]['minutes']; @endphp
                            @foreach ($wrapped['gaming']['top_games'] as $i => $game)
                                @php
                                    $gh = intdiv($game['minutes'], 60);
                                    $gm = $game['minutes'] % 60;
                                    $gt = $gm > 0 ? "{$gh}h {$gm}m" : "{$gh}h";
                                @endphp
                                <a href="{{ route('playstation.show', $game['id']) }}" class="wrapped__rank-item">
                                    <div class="wrapped__rank-num">{{ $i + 1 }}</div>
                                    @if ($game['image_url'])
                                        <img src="{{ $game['image_url'] }}" alt="{{ $game['name'] }}" class="wrapped__rank-thumb">
                                    @else
                                        <div class="wrapped__rank-thumb wrapped__rank-thumb--empty wrapped__rank-thumb--gaming"></div>
                                    @endif
                                    <div class="wrapped__rank-info">
                                        <div class="wrapped__rank-name">{{ $game['name'] }}</div>
                                        <div class="wrapped__rank-sub">{{ $game['sessions'] }} sessions</div>
                                        <div class="wrapped__rank-bar-wrap">
                                            <div class="wrapped__rank-bar wrapped__rank-bar--gaming" style="width: {{ round($game['minutes'] / $maxGameMins * 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="wrapped__rank-count">{{ $gt }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        @endif

        {{-- ── Movies ──────────────────────────────────────────────────── --}}
        @if ($wrapped['movies']['total_watches'] > 0)
            <div class="wrapped__chapter wrapped__chapter--media" x-data="{ open: true }">
                <button class="wrapped__chapter-header" @click="open = !open" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" class="wrapped__chapter-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                    <span class="wrapped__chapter-title">Movies</span>
                    <svg class="wrapped__chapter-chevron" :class="{ 'wrapped__chapter-chevron--open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="wrapped__chapter-body" x-show="open" x-transition>

                    <div class="wrapped__stats-row">
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['movies']['total_watches']) }}</div>
                            <div class="wrapped__stat-label">Movies watched</div>
                        </div>
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['movies']['unique_movies']) }}</div>
                            <div class="wrapped__stat-label">Unique titles</div>
                        </div>
                        @if ($wrapped['movies']['total_runtime_minutes'] > 0)
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ number_format(intdiv($wrapped['movies']['total_runtime_minutes'], 60)) }}</div>
                                <div class="wrapped__stat-label">Hours of film</div>
                            </div>
                        @endif
                        @if ($wrapped['movies']['best_month'])
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ $wrapped['movies']['best_month']['label'] }}</div>
                                <div class="wrapped__stat-label">Most active month</div>
                            </div>
                        @endif
                    </div>

                    @if (count($wrapped['movies']['top_movies']) > 0)
                        <div class="wrapped__poster-list">
                            @foreach ($wrapped['movies']['top_movies'] as $i => $movie)
                                <a href="{{ route('movies.show', $movie['id']) }}" class="wrapped__poster-item">
                                    <div class="wrapped__poster-rank">{{ $i + 1 }}</div>
                                    <div class="wrapped__poster-img-wrap">
                                        @if ($movie['poster_url'])
                                            <img src="{{ $movie['poster_url'] }}" alt="{{ $movie['title'] }}" class="wrapped__poster-img">
                                        @else
                                            <div class="wrapped__poster-img wrapped__poster-img--empty wrapped__poster-img--media"></div>
                                        @endif
                                    </div>
                                    <div class="wrapped__poster-title">{{ $movie['title'] }}</div>
                                    @if ($movie['count'] > 1)
                                        <div class="wrapped__poster-sub">{{ $movie['count'] }}×</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        @endif

        {{-- ── TV ──────────────────────────────────────────────────────── --}}
        @if ($wrapped['tv']['total_episodes'] > 0)
            <div class="wrapped__chapter wrapped__chapter--tv" x-data="{ open: true }">
                <button class="wrapped__chapter-header" @click="open = !open" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" class="wrapped__chapter-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="wrapped__chapter-title">TV</span>
                    <svg class="wrapped__chapter-chevron" :class="{ 'wrapped__chapter-chevron--open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="wrapped__chapter-body" x-show="open" x-transition>

                    <div class="wrapped__stats-row">
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['tv']['total_episodes']) }}</div>
                            <div class="wrapped__stat-label">Episodes watched</div>
                        </div>
                        @if ($wrapped['tv']['total_runtime_minutes'] > 0)
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ number_format(intdiv($wrapped['tv']['total_runtime_minutes'], 60)) }}</div>
                                <div class="wrapped__stat-label">Hours of TV</div>
                            </div>
                        @endif
                        @if ($wrapped['tv']['best_month'])
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ $wrapped['tv']['best_month']['label'] }}</div>
                                <div class="wrapped__stat-label">Most active month</div>
                            </div>
                        @endif
                    </div>

                    @if (count($wrapped['tv']['top_series']) > 0)
                        <div class="wrapped__poster-list">
                            @foreach ($wrapped['tv']['top_series'] as $i => $series)
                                <a href="{{ route('tv.show', $series['id']) }}" class="wrapped__poster-item">
                                    <div class="wrapped__poster-rank">{{ $i + 1 }}</div>
                                    <div class="wrapped__poster-img-wrap">
                                        @if ($series['poster_url'])
                                            <img src="{{ $series['poster_url'] }}" alt="{{ $series['name'] }}" class="wrapped__poster-img">
                                        @else
                                            <div class="wrapped__poster-img wrapped__poster-img--empty wrapped__poster-img--tv"></div>
                                        @endif
                                    </div>
                                    <div class="wrapped__poster-title">{{ $series['name'] }}</div>
                                    <div class="wrapped__poster-sub">{{ $series['episodes'] }} ep</div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        @endif

        {{-- ── Health ───────────────────────────────────────────────────── --}}
        @if ($wrapped['health']['total_steps'] > 0)
            <div class="wrapped__chapter wrapped__chapter--health" x-data="{ open: true }">
                <button class="wrapped__chapter-header" @click="open = !open" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" class="wrapped__chapter-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span class="wrapped__chapter-title">Health</span>
                    <svg class="wrapped__chapter-chevron" :class="{ 'wrapped__chapter-chevron--open': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="wrapped__chapter-body" x-show="open" x-transition>

                    <div class="wrapped__stats-row" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['health']['total_steps']) }}</div>
                            <div class="wrapped__stat-label">Steps taken</div>
                        </div>
                        @if ($wrapped['health']['km_walked'] > 0)
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ number_format($wrapped['health']['km_walked']) }}</div>
                                <div class="wrapped__stat-label">km walked</div>
                            </div>
                        @endif
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['health']['days_logged']) }}</div>
                            <div class="wrapped__stat-label">Days logged</div>
                        </div>
                        <div class="wrapped__stat">
                            <div class="wrapped__stat-value">{{ number_format($wrapped['health']['avg_steps']) }}</div>
                            <div class="wrapped__stat-label">Daily average</div>
                        </div>
                        @if ($wrapped['health']['best_day'])
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ number_format($wrapped['health']['best_day']['steps']) }}</div>
                                <div class="wrapped__stat-label">Best day ({{ $wrapped['health']['best_day']['date']->format('M j') }})</div>
                            </div>
                        @endif
                        @if ($wrapped['health']['best_month'])
                            <div class="wrapped__stat">
                                <div class="wrapped__stat-value">{{ $wrapped['health']['best_month']['label'] }}</div>
                                <div class="wrapped__stat-label">Most active month</div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @endif

        {{-- Empty state --}}
        @if (
            $wrapped['music']['total_plays'] === 0 &&
            $wrapped['gaming']['total_minutes'] === 0 &&
            $wrapped['movies']['total_watches'] === 0 &&
            $wrapped['tv']['total_episodes'] === 0 &&
            $wrapped['health']['total_steps'] === 0
        )
            <x-ui.empty-state
                title="No data for {{ $wrapped['year'] }}"
                description="Start tracking to see your year in review."
            />
        @endif

    </div>

</x-layouts.app>
