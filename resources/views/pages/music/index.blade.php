<x-layouts.app title="Music">

    <x-layout.page-header title="Music">
        <x-slot:actions>
            <a href="{{ route('music.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
            <form method="POST" action="{{ route('music.sync') }}">
                @csrf
                <button type="submit" class="btn btn--primary btn--sm">Sync now</button>
            </form>
            <form method="POST" action="{{ route('spotify.disconnect') }}">
                @csrf
                <button type="submit" class="btn btn--secondary btn--sm">Disconnect Spotify</button>
            </form>
        </x-slot:actions>
    </x-layout.page-header>

    @if($lastSync)
        <p class="music-header__meta" style="margin-bottom: 1.5rem;">
            Last synced {{ $lastSync->synced_at->diffForHumans() }} — {{ number_format($lastSync->plays_imported) }} plays imported
        </p>
    @endif

    @if($obsessionSlides->isNotEmpty())
        <div
            class="media-slider media-slider--blur"
            x-data="{
                slides: @js($obsessionSlides),
                currentSlide: 0,
                init() {
                    if (this.slides.length > 1) {
                        setInterval(() => {
                            this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                        }, 3500);
                    }
                }
            }"
        >
            <template x-for="(slide, i) in slides" :key="i">
                <div
                    class="media-slider__slide"
                    x-show="currentSlide === i"
                    x-transition.opacity.duration.600ms
                >
                    <div class="media-slider__blur-bg" :style="`background-image: url('${slide.image}')`"></div>
                    <div class="media-slider__overlay"></div>
                    <div class="media-slider__cover">
                        <img :src="slide.image" :alt="slide.name">
                    </div>
                    <div class="media-slider__info">
                        <div class="media-slider__label" x-text="slide.name"></div>
                        <div class="media-slider__stats" x-text="slide.artist + ' · ' + slide.plays + '× since obsession'"></div>
                    </div>
                </div>
            </template>
        </div>
    @endif

    @if($currentlyPlaying)
        <div class="now-playing">
            @if($currentlyPlaying['album_image_url'])
                <img src="{{ $currentlyPlaying['album_image_url'] }}" alt="{{ $currentlyPlaying['album_name'] }}" class="now-playing__cover">
            @endif
            <div class="now-playing__info">
                <div class="now-playing__track">{{ $currentlyPlaying['track_name'] }}</div>
                <div class="now-playing__artist">{{ $currentlyPlaying['artist_names'] }} · {{ $currentlyPlaying['album_name'] }}</div>
                @if($currentlyPlaying['duration_ms'] > 0)
                    <div class="now-playing__progress">
                        <div class="now-playing__progress-bar" style="width: {{ round(($currentlyPlaying['progress_ms'] / $currentlyPlaying['duration_ms']) * 100) }}%"></div>
                    </div>
                @endif
            </div>
            <span class="badge" style="background: #1db954; color: #000; font-size: 0.6875rem;">NOW PLAYING</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="lg:col-span-2">
            <x-ui.card title="Recent plays">
                <x-slot:action>
                    <span class="text-xs text-[var(--color-text-muted)]">{{ number_format($recentPlays->total()) }} total</span>
                </x-slot:action>

                @if($recentPlays->isEmpty())
                    <x-ui.empty-state title="No plays yet" description="Connect Spotify and sync to see your listening history." />
                @else
                    <div class="play-list">
                        @foreach($recentPlays as $play)
                            <div class="play-item">
                                @if($play->track?->album?->image_url)
                                    <img src="{{ $play->track->album->image_url }}" alt="{{ $play->track->album->name }}" class="play-item__cover">
                                @else
                                    <div class="play-item__cover"></div>
                                @endif
                                <div class="play-item__info">
                                    <a href="{{ route('music.tracks.show', $play->track) }}" class="play-item__title">
                                        {{ $play->track?->title ?? '—' }}
                                    </a>
                                    <div class="play-item__meta">
                                        @if($play->track?->artists->isNotEmpty())
                                            @foreach($play->track->artists as $artist)
                                                <a href="{{ route('music.artists.show', $artist) }}" class="hover:underline">{{ $artist->name }}</a>
                                                @if(!$loop->last)<span> · </span>@endif
                                            @endforeach
                                            ·
                                        @endif
                                        @if($play->track?->album)
                                            <a href="{{ route('music.albums.show', $play->track->album) }}" class="hover:underline">{{ $play->track->album->name }}</a>
                                        @endif
                                    </div>
                                    @if($play->track?->genres)
                                        <div class="play-item__genres">
                                            @foreach($play->track->genres as $genre)
                                                <span class="badge badge--muted play-item__genre">{{ $genre }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <span class="play-item__time">{{ $play->played_at->format('d M, H:i') }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($recentPlays->hasPages())
                        <div class="card__footer">
                            {{ $recentPlays->links() }}
                        </div>
                    @endif
                @endif
            </x-ui.card>
        </div>

        <div class="flex flex-col gap-6">

            <x-ui.card title="Top tracks this week">
                @if($topTracksThisWeek->isEmpty())
                    <x-ui.empty-state title="No data" description="Listen to some tracks first." />
                @else
                    <div class="play-list">
                        @foreach($topTracksThisWeek as $row)
                            <div class="play-item">
                                @if($row->track?->album?->image_url)
                                    <img src="{{ $row->track->album->image_url }}" alt="" class="play-item__cover">
                                @else
                                    <div class="play-item__cover"></div>
                                @endif
                                <div class="play-item__info">
                                    <a href="{{ route('music.tracks.show', $row->track) }}" class="play-item__title">
                                        {{ $row->track?->title ?? '—' }}
                                    </a>
                                    <div class="play-item__meta">{{ $row->track?->artists_string }}</div>
                                </div>
                                <span class="play-item__time">{{ $row->play_count }}×</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Top artists this week">
                @if($topArtistsThisWeek->isEmpty())
                    <x-ui.empty-state title="No data" description="Listen to some tracks first." />
                @else
                    <div class="play-list">
                        @foreach($topArtistsThisWeek as $artist)
                            <div class="play-item">
                                @if($artist->image_url)
                                    <img src="{{ $artist->image_url }}" alt="{{ $artist->name }}" class="music-artist-photo">
                                @else
                                    <div class="music-artist-photo"></div>
                                @endif
                                <div class="play-item__info">
                                    <a href="{{ route('music.artists.show', $artist) }}" class="play-item__title">
                                        {{ $artist->name }}
                                    </a>
                                </div>
                                <span class="play-item__time">{{ $artist->play_count }}×</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Obsessions">
                @if($obsessions->isEmpty())
                    <x-ui.empty-state title="No obsessions yet" description="Mark a track as obsession from its detail page." />
                @else
                    <div class="play-list">
                        @foreach($obsessions as $track)
                            <div class="play-item">
                                @if($track->album?->image_url)
                                    <img src="{{ $track->album->image_url }}" alt="" class="play-item__cover">
                                @else
                                    <div class="play-item__cover"></div>
                                @endif
                                <div class="play-item__info">
                                    <a href="{{ route('music.tracks.show', $track) }}" class="play-item__title">
                                        {{ $track->title }}
                                    </a>
                                    <div class="play-item__meta">{{ $track->artists_string }}</div>
                                </div>
                                <span class="play-item__time">{{ $track->plays_since_obsession }}×</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

        </div>

    </div>

    <x-layout.notification />

</x-layouts.app>
