<x-layouts.app title="{{ $person->name }}">

    <x-layout.page-header :title="$person->name">
        <x-slot:actions>
            <a href="javascript:history.back()" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Profile card --}}
    <x-ui.card class="mb-6">
        <div class="people-profile">
            @if ($person->profile_url)
                <img src="{{ $person->profile_url }}" alt="{{ $person->name }}" class="people-profile__photo">
            @else
                <div class="people-profile__photo people-profile__photo--empty"></div>
            @endif

            <div class="people-profile__stats">
                <div class="people-profile__stat">
                    <div class="people-profile__stat-value">{{ $movies->count() }}</div>
                    <div class="people-profile__stat-label">movies</div>
                </div>
                <div class="people-profile__stat">
                    <div class="people-profile__stat-value">{{ $totalMovieWatches }}</div>
                    <div class="people-profile__stat-label">movie watches</div>
                </div>
                <div class="people-profile__stat">
                    <div class="people-profile__stat-value">{{ $tvSeries->count() }}</div>
                    <div class="people-profile__stat-label">TV series</div>
                </div>
                <div class="people-profile__stat">
                    <div class="people-profile__stat-value">{{ $totalEpisodesWatched }}</div>
                    <div class="people-profile__stat-label">episodes watched</div>
                </div>
                @if ($totalHours > 0)
                    <div class="people-profile__stat">
                        <div class="people-profile__stat-value">{{ $totalHours }}h</div>
                        <div class="people-profile__stat-label">total watch time</div>
                    </div>
                @endif
                @if ($firstSeen)
                    <div class="people-profile__stat">
                        <div class="people-profile__stat-value">{{ $firstSeen->format('d M Y') }}</div>
                        <div class="people-profile__stat-label">first seen</div>
                    </div>
                @endif
                @if ($lastSeen)
                    <div class="people-profile__stat">
                        <div class="people-profile__stat-value">{{ $lastSeen->format('d M Y') }}</div>
                        <div class="people-profile__stat-label">last seen</div>
                    </div>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{-- Films --}}
    @if ($movies->isNotEmpty())
        <x-ui.card title="Films ({{ $movies->count() }})" class="mb-6">
            <div class="media-cast media-cast--grid">
                @foreach ($movies as $movie)
                    <a href="{{ route('movies.show', $movie) }}" class="media-cast__member">
                        @if ($movie->poster_url)
                            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="media-cast__photo">
                        @else
                            <div class="media-cast__photo media-cast__photo--empty"></div>
                        @endif
                        <div class="media-cast__name">{{ $movie->title }}</div>
                        @if ($movie->pivot->character)
                            <div class="media-cast__role">{{ $movie->pivot->character }}</div>
                        @elseif ($movie->pivot->job)
                            <div class="media-cast__role">{{ $movie->pivot->job }}</div>
                        @endif
                        @if ($movie->watches->count() > 1)
                            <div class="media-cast__episodes">{{ $movie->watches->count() }}×</div>
                        @endif
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- TV Series --}}
    @if ($tvSeries->isNotEmpty())
        <x-ui.card title="TV Series ({{ $tvSeries->count() }})">
            <div class="people-series-list">
                @foreach ($tvSeries as $show)
                    <a href="{{ route('tv.show', $show) }}" class="people-series-item">
                        @if ($show->poster_url)
                            <img src="{{ $show->poster_url }}" alt="{{ $show->name }}" class="people-series-item__poster">
                        @else
                            <div class="people-series-item__poster people-series-item__poster--empty"></div>
                        @endif
                        <div class="people-series-item__info">
                            <div class="people-series-item__name">{{ $show->name_en ?? $show->name }}</div>
                            @if ($show->pivot->character)
                                <div class="people-series-item__role">{{ $show->pivot->character }}</div>
                            @elseif ($show->pivot->job)
                                <div class="people-series-item__role">{{ $show->pivot->job }}</div>
                            @endif
                            <div class="people-series-item__meta">
                                @if ($show->pivot->episode_count)
                                    <span>{{ $show->pivot->episode_count }} ep in series</span>
                                @endif
                                @if ($show->episodes_watched > 0)
                                    <span class="text-green-400">{{ $show->episodes_watched }} watched</span>
                                @endif
                            </div>
                        </div>
                        <div class="people-series-item__right">
                            @if ($show->episodes_watched > 0)
                                <div class="people-series-item__completion">{{ round($show->completion_percentage) }}%</div>
                            @endif
                            @if ($show->last_watched_at)
                                <div class="people-series-item__date">{{ $show->last_watched_at->format('d M Y') }}</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    @endif

</x-layouts.app>
