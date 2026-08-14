<x-layouts.app :title="$artist->name">

    <x-layout.page-header :title="$artist->name">
        <x-slot:actions>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="track-detail">
        @if($artist->image_url)
            <img src="{{ $artist->image_url }}" alt="{{ $artist->name }}" class="track-detail__cover" style="border-radius: 9999px;">
        @endif
        <div class="track-detail__info">
            <h2 class="track-detail__title">{{ $artist->name }}</h2>
            @if($artist->genres && count($artist->genres) > 0)
                <div class="flex flex-wrap gap-1.5 mt-1">
                    @foreach(array_slice($artist->genres, 0, 5) as $genre)
                        <span class="badge badge--muted">{{ $genre }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mt-2">
        <x-stats.stat-card
            label="Listening time"
            :value="$totalListeningFormatted"
            icon="clock"
        />
        <x-stats.stat-card
            label="Total plays"
            :value="number_format($totalPlays)"
            icon="play"
        />
        <x-stats.stat-card
            label="Unique tracks"
            :value="number_format($uniqueTracks)"
            icon="musical-note"
        />
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

    <x-layout.notification />

</x-layouts.app>
