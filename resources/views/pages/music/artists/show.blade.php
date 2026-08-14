<x-layouts.app :title="$artist->name">

    {{-- Slider header --}}
    @if(count($sliderImages) > 0)
        <div
            class="media-slider"
            style="margin-bottom: 0;"
            x-data="{
                slides: @js($sliderImages),
                current: 0,
                timer: null,
                init() {
                    if (this.slides.length > 1) {
                        this.timer = setInterval(() => {
                            this.current = (this.current + 1) % this.slides.length;
                        }, 5000);
                    }
                }
            }"
        >
            <template x-for="(url, i) in slides" :key="i">
                <div
                    class="media-slider__slide"
                    :style="{ backgroundImage: `url('${url}')`, opacity: current === i ? 1 : 0, transition: 'opacity 0.8s ease-in-out' }"
                >
                    <div class="media-slider__overlay"></div>
                </div>
            </template>
        </div>
    @else
        <div style="height: 26rem; background: var(--color-bg-secondary); border-radius: var(--radius-lg);"></div>
    @endif

    <div class="media-detail">

        <div class="media-detail__main">

            @if($artist->image_url)
                <div class="media-detail__poster-wrap">
                    <img
                        src="{{ $artist->image_url }}"
                        alt="{{ $artist->name }}"
                        class="media-detail__poster"
                        style="border-radius: 9999px;"
                    >
                </div>
            @endif

            <div class="media-detail__info">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <h1 class="media-detail__title">{{ $artist->name }}</h1>
                    <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
                </div>

                @if($artist->genres && count($artist->genres) > 0)
                    <div class="media-detail__genres">
                        @foreach(array_slice($artist->genres, 0, 5) as $genre)
                            <span class="badge badge--muted">{{ $genre }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="media-detail__stats-row">
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value">{{ $totalListeningFormatted }}</div>
                        <div class="media-detail__stat-label">listening time</div>
                    </div>
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value">{{ number_format($totalPlays) }}</div>
                        <div class="media-detail__stat-label">total plays</div>
                    </div>
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value">{{ number_format($uniqueTracks) }}</div>
                        <div class="media-detail__stat-label">unique tracks</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 gap-6 mt-6 lg:grid-cols-2">

            <x-ui.card title="Top tracks">
                @if($topTracks->isEmpty())
                    <x-ui.empty-state title="No tracks found" />
                @else
                    <div class="play-list">
                        @foreach($topTracks as $track)
                            @php $trackPlayCount = $track->plays->count(); @endphp
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
                                    @if($track->album)
                                        <div class="play-item__meta">
                                            <a href="{{ route('music.albums.show', $track->album) }}" class="hover:underline">{{ $track->album->name }}</a>
                                        </div>
                                    @endif
                                </div>
                                <span class="play-item__time">{{ $trackPlayCount }}×</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Albums">
                @if($albums->isEmpty())
                    <x-ui.empty-state title="No albums found" />
                @else
                    <div class="play-list">
                        @foreach($albums as $album)
                            <div class="play-item">
                                @if($album->image_url)
                                    <img src="{{ $album->image_url }}" alt="{{ $album->name }}" class="play-item__cover">
                                @else
                                    <div class="play-item__cover"></div>
                                @endif
                                <div class="play-item__info">
                                    <a href="{{ route('music.albums.show', $album) }}" class="play-item__title">
                                        {{ $album->name }}
                                    </a>
                                    <div class="play-item__meta">
                                        {{ $album->release_year }}
                                        @if($album->album_type)· {{ ucfirst($album->album_type) }}@endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

        </div>

        <x-ui.card title="Play history" class="mt-6">
            @if($recentPlays->isEmpty())
                <x-ui.empty-state title="No plays recorded" />
            @else
                <div class="play-list">
                    @foreach($recentPlays as $play)
                        <div class="play-item">
                            @if($play->track->album?->image_url)
                                <img src="{{ $play->track->album->image_url }}" alt="" class="play-item__cover">
                            @else
                                <div class="play-item__cover"></div>
                            @endif
                            <div class="play-item__info">
                                <a href="{{ route('music.tracks.show', $play->track) }}" class="play-item__title">
                                    {{ $play->track->title }}
                                </a>
                                @if($play->track->album)
                                    <div class="play-item__meta">
                                        <a href="{{ route('music.albums.show', $play->track->album) }}" class="hover:underline">{{ $play->track->album->name }}</a>
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

    <x-layout.notification />

</x-layouts.app>
