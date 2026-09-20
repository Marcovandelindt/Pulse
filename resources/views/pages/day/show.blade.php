<x-layouts.app :title="$date->format('M j, Y') . ' — On This Day'">

    <div x-data="{ date: '{{ $date->format('Y-m-d') }}' }">

    {{-- Date navigation header --}}
    <div class="day-header">
        <a href="{{ route('day.show', $date->copy()->subDay()->format('Y-m-d')) }}" class="day-header__nav">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width:1rem;height:1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Prev
        </a>

        <div class="day-header__center">
            <div class="day-header__title">{{ $date->format('l, F j, Y') }}</div>
            <div class="day-header__picker">
                <input
                    type="date"
                    x-model="date"
                    @change="window.location.href = '{{ url('/day') }}/' + date"
                    class="day-header__input"
                    max="{{ today()->format('Y-m-d') }}"
                    value="{{ $date->format('Y-m-d') }}"
                >
            </div>
        </div>

        @if($date->lt(today()))
            <a href="{{ route('day.show', $date->copy()->addDay()->format('Y-m-d')) }}" class="day-header__nav">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width:1rem;height:1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        @else
            <div class="day-header__nav day-header__nav--disabled">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width:1rem;height:1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </div>
        @endif
    </div>

    @if(!$hasActivity)

        <div class="day-empty">
            <div class="day-empty__icon">📅</div>
            <div class="day-empty__title">Nothing tracked on this day</div>
            <div class="day-empty__sub">No health, gaming, music or entertainment data found for {{ $date->format('F j, Y') }}.</div>
        </div>

    @else

        {{-- Summary stat row --}}
        <div class="stats-row" style="margin-bottom: 1.5rem;">
            @if($health?->steps)
                <x-stats.stat-card label="Steps" :value="number_format($health->steps)" icon="heart" />
            @endif
            @if($sleep)
                <x-stats.stat-card label="Sleep" :value="$sleep->formattedMinutes($sleep->total_sleep_minutes)" icon="moon" />
            @endif
            @if($gamingFormatted)
                <x-stats.stat-card label="Gaming" :value="$gamingFormatted" icon="gamepad" />
            @endif
            @if($plays->count() > 0)
                <x-stats.stat-card label="Tracks" :value="$plays->count() . ' tracks'" icon="musical-note" />
            @endif
            @php $watchCount = $movies->count() + $episodesBySeries->flatten()->count(); @endphp
            @if($watchCount > 0)
                <x-stats.stat-card label="Watched" :value="$watchCount . ' ' . ($watchCount === 1 ? 'title' : 'titles')" icon="film" />
            @endif
        </div>

        {{-- Content grid --}}
        <div class="day-grid">

            {{-- Health & Sleep --}}
            @if($health || $sleep)
                <x-ui.card>
                    <div class="day-section__header">
                        <span class="day-section__icon">🏃</span>
                        <span class="day-section__title">Health & Sleep</span>
                    </div>

                    <div class="day-health">

                        @if($sleep)
                            <div class="day-health__block">
                                <div class="day-health__block-label">Sleep</div>
                                <div class="day-health__block-value">{{ $sleep->formattedMinutes($sleep->total_sleep_minutes) }}</div>
                                @if($sleep->sleepScore())
                                    <div class="day-health__block-sub">
                                        <span class="day-health__score day-health__score--{{ strtolower($sleep->sleepScoreLabel()) }}">{{ $sleep->sleepScoreLabel() }}</span>
                                    </div>
                                @endif
                                @if($sleep->rem_minutes || $sleep->deep_minutes || $sleep->core_minutes)
                                    <div class="day-health__sleep-breakdown">
                                        @if($sleep->rem_minutes)
                                            <span class="day-health__sleep-stage day-health__sleep-stage--rem">
                                                REM {{ $sleep->formattedMinutes($sleep->rem_minutes) }}
                                            </span>
                                        @endif
                                        @if($sleep->deep_minutes)
                                            <span class="day-health__sleep-stage day-health__sleep-stage--deep">
                                                Deep {{ $sleep->formattedMinutes($sleep->deep_minutes) }}
                                            </span>
                                        @endif
                                        @if($sleep->core_minutes)
                                            <span class="day-health__sleep-stage day-health__sleep-stage--core">
                                                Core {{ $sleep->formattedMinutes($sleep->core_minutes) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($health)
                            @if($health->steps)
                                <div class="day-health__row">
                                    <span class="day-health__row-icon">🚶</span>
                                    <span class="day-health__row-label">Steps</span>
                                    <span class="day-health__row-value">{{ number_format($health->steps) }}</span>
                                </div>
                            @endif
                            @if($health->heart_rate_avg || $health->resting_heart_rate)
                                <div class="day-health__row">
                                    <span class="day-health__row-icon">❤️</span>
                                    <span class="day-health__row-label">Heart rate</span>
                                    <span class="day-health__row-value">
                                        {{ implode(' · ', array_filter([
                                            $health->heart_rate_avg ? 'avg ' . $health->heart_rate_avg . ' bpm' : null,
                                            $health->resting_heart_rate ? 'resting ' . $health->resting_heart_rate . ' bpm' : null,
                                        ])) }}
                                    </span>
                                </div>
                            @endif
                            @if($health->active_calories || $health->basal_calories)
                                <div class="day-health__row">
                                    <span class="day-health__row-icon">🔥</span>
                                    <span class="day-health__row-label">Calories</span>
                                    <span class="day-health__row-value">
                                        {{ implode(' · ', array_filter([
                                            $health->active_calories ? number_format($health->active_calories) . ' active' : null,
                                            $health->basal_calories ? number_format($health->basal_calories) . ' basal' : null,
                                        ])) }}
                                    </span>
                                </div>
                            @endif
                            @if($health->exercise_minutes)
                                <div class="day-health__row">
                                    <span class="day-health__row-icon">💪</span>
                                    <span class="day-health__row-label">Exercise</span>
                                    <span class="day-health__row-value">{{ $health->exercise_minutes }} min</span>
                                </div>
                            @endif
                        @endif

                    </div>
                </x-ui.card>
            @endif

            {{-- Gaming --}}
            @if($sessions->isNotEmpty() || $nintendoRecords->isNotEmpty())
                <x-ui.card>
                    <div class="day-section__header">
                        <span class="day-section__icon">🎮</span>
                        <span class="day-section__title">Gaming</span>
                        @if($gamingFormatted)
                            <span class="day-section__meta">{{ $gamingFormatted }}</span>
                        @endif
                    </div>

                    @if($sessions->isNotEmpty())
                        @if($nintendoRecords->isNotEmpty())
                            <div class="day-platform-label">PlayStation</div>
                        @endif
                        <div class="day-sessions">
                            @foreach($sessions as $session)
                                <a href="{{ route('playstation.show', $session->game) }}" class="day-session">
                                    @if($session->game->image_url)
                                        <img src="{{ $session->game->image_url }}" alt="" class="day-session__cover">
                                    @else
                                        <div class="day-session__cover day-session__cover--placeholder">🎮</div>
                                    @endif
                                    <div class="day-session__info">
                                        <div class="day-session__game">{{ $session->game->label }}</div>
                                        <div class="day-session__time">{{ $session->started_at->format('H:i') }} – {{ $session->end_time->format('H:i') }}</div>
                                    </div>
                                    <div class="day-session__duration">{{ $session->formatted_duration }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($nintendoRecords->isNotEmpty())
                        @if($sessions->isNotEmpty())
                            <div class="day-platform-label" style="margin-top:0.75rem;">Nintendo Switch</div>
                        @endif
                        <div class="day-sessions">
                            @foreach($nintendoRecords as $record)
                                <div class="day-session">
                                    @if($record->game?->image_url)
                                        <img src="{{ $record->game->image_url }}" alt="" class="day-session__cover">
                                    @else
                                        <div class="day-session__cover day-session__cover--placeholder">🕹️</div>
                                    @endif
                                    <div class="day-session__info">
                                        <div class="day-session__game">{{ $record->game?->name ?? 'Unknown game' }}</div>
                                        <div class="day-session__time">Nintendo Switch</div>
                                    </div>
                                    @php
                                        $h = intdiv($record->minutes_played, 60);
                                        $m = $record->minutes_played % 60;
                                        $label = ($h > 0 && $m > 0) ? "{$h}h {$m}m" : ($h > 0 ? "{$h}h" : "{$m}m");
                                    @endphp
                                    <div class="day-session__duration">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            @endif

            {{-- Music --}}
            @if($plays->isNotEmpty())
                <x-ui.card>
                    <div class="day-section__header">
                        <span class="day-section__icon">🎵</span>
                        <span class="day-section__title">Music</span>
                        <span class="day-section__meta">{{ $plays->count() }} {{ $plays->count() === 1 ? 'track' : 'tracks' }}</span>
                    </div>

                    @php
                        $artistCounts = $plays
                            ->flatMap(fn ($p) => $p->track->artists->values())
                            ->groupBy('id')
                            ->map(fn ($g) => ['model' => $g->first(), 'count' => $g->count()])
                            ->sortByDesc('count')
                            ->values()
                            ->take(3);
                    @endphp

                    @if($artistCounts->isNotEmpty())
                        <div class="day-top-artists">
                            @foreach($artistCounts as $entry)
                                <a href="{{ route('music.artists.show', $entry['model']) }}" class="day-top-artist">
                                    @if($entry['model']->image_url)
                                        <img src="{{ $entry['model']->image_url }}" alt="" class="day-top-artist__avatar">
                                    @else
                                        <div class="day-top-artist__avatar day-top-artist__avatar--placeholder">♪</div>
                                    @endif
                                    <div class="day-top-artist__info">
                                        <span class="day-top-artist__name">{{ $entry['model']->name }}</span>
                                        <span class="day-top-artist__count">{{ $entry['count'] }} {{ $entry['count'] === 1 ? 'play' : 'plays' }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="day-tracks-list">
                        @foreach($plays as $play)
                            <a href="{{ route('music.tracks.show', $play->track) }}" class="day-track">
                                @if($play->track->album?->image_url)
                                    <img src="{{ $play->track->album->image_url }}" alt="" class="day-track__cover">
                                @else
                                    <div class="day-track__cover day-track__cover--placeholder">♪</div>
                                @endif
                                <div class="day-track__info">
                                    <div class="day-track__title">{{ $play->track->title }}</div>
                                    <div class="day-track__artist">{{ $play->track->artists_string }}</div>
                                </div>
                                <span class="day-track__time">{{ $play->played_at->format('H:i') }}</span>
                            </a>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            {{-- Entertainment --}}
            @if($movies->isNotEmpty() || $episodesBySeries->isNotEmpty())
                <x-ui.card>
                    <div class="day-section__header">
                        <span class="day-section__icon">📺</span>
                        <span class="day-section__title">Entertainment</span>
                        @if($watchTimeFormatted)
                            <span class="day-section__meta">{{ $watchTimeFormatted }}</span>
                        @endif
                    </div>

                    @if($movies->isNotEmpty())
                        <div class="day-platform-label">Movies</div>
                        <div class="day-media-list">
                            @foreach($movies as $watch)
                                <a href="{{ route('movies.show', $watch->movie) }}" class="day-media-item">
                                    @if($watch->movie->poster_url)
                                        <img src="{{ $watch->movie->poster_url }}" alt="" class="day-media-item__poster">
                                    @else
                                        <div class="day-media-item__poster day-media-item__poster--placeholder">🎬</div>
                                    @endif
                                    <div class="day-media-item__info">
                                        <div class="day-media-item__title">{{ $watch->movie->title }}</div>
                                        <div class="day-media-item__meta">
                                            {{ $watch->movie->release_date?->year }}
                                            @if($watch->movie->runtime)
                                                · {{ intdiv($watch->movie->runtime, 60) }}h {{ $watch->movie->runtime % 60 }}m
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($episodesBySeries->isNotEmpty())
                        @if($movies->isNotEmpty())
                            <div style="margin-top: 0.75rem;"></div>
                        @endif
                        <div class="day-platform-label">TV</div>
                        <div class="day-episodes">
                            @foreach($episodesBySeries as $seriesId => $seriesWatches)
                                @php $series = $seriesWatches->first()->episode->season->series; @endphp
                                <div class="day-series">
                                    <a href="{{ route('tv.show', $series) }}" class="day-series__header">
                                        @if($series->poster_url)
                                            <img src="{{ $series->poster_url }}" alt="" class="day-series__poster">
                                        @else
                                            <div class="day-series__poster day-series__poster--placeholder">📺</div>
                                        @endif
                                        <div class="day-series__info">
                                            <span class="day-series__name">{{ $series->name }}</span>
                                            <span class="day-series__count">{{ $seriesWatches->count() }} {{ $seriesWatches->count() === 1 ? 'episode' : 'episodes' }}</span>
                                        </div>
                                    </a>
                                    <div class="day-series__episodes">
                                        @foreach($seriesWatches as $watch)
                                            <div class="day-episode">
                                                <span class="day-episode__code">S{{ $watch->episode->season->season_number }}E{{ $watch->episode->episode_number }}</span>
                                                <span class="day-episode__title">{{ $watch->episode->name }}</span>
                                                @if($watch->episode->runtime)
                                                    <span class="day-episode__time">{{ $watch->episode->runtime }}m</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            @endif

        </div>{{-- .day-grid --}}

    @endif

    </div>{{-- x-data --}}

    <x-layout.notification />

</x-layouts.app>
