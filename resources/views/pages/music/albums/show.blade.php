<x-layouts.app :title="$album->name">

    <x-layout.page-header :title="$album->name">
        <x-slot:actions>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="track-detail">
        @if($album->image_url)
            <img src="{{ $album->image_url }}" alt="{{ $album->name }}" class="track-detail__cover">
        @endif
        <div class="track-detail__info">
            <h2 class="track-detail__title">{{ $album->name }}</h2>
            <div class="track-detail__meta">
                @if($album->release_year)
                    <span>{{ $album->release_year }}</span>
                    <span>·</span>
                @endif
                @if($album->album_type)
                    <span>{{ ucfirst($album->album_type) }}</span>
                    <span>·</span>
                @endif
                <span>{{ $album->total_tracks }} tracks</span>
            </div>

            <div class="flex flex-wrap gap-4 mt-4">
                <div>
                    <div class="text-xs text-[var(--color-text-muted)] uppercase tracking-wide">Total plays</div>
                    <div class="text-sm font-medium text-[var(--color-text-primary)] mt-0.5">{{ number_format($totalPlays) }}</div>
                </div>
            </div>
        </div>
    </div>

    <x-ui.card title="Tracks">
        @if($album->tracks->isEmpty())
            <x-ui.empty-state title="No tracks found" />
        @else
            <div class="play-list">
                @foreach($album->tracks->sortBy('title') as $index => $track)
                    @php $trackPlayCount = $track->plays->count(); @endphp
                    <div class="play-item">
                        <span class="play-item__time" style="min-width: 1.5rem; text-align: right;">{{ $index + 1 }}</span>
                        <div class="play-item__info">
                            <a href="{{ route('music.tracks.show', $track) }}" class="play-item__title">
                                {{ $track->title }}
                            </a>
                            <div class="play-item__meta">{{ $track->artists_string }}</div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="play-item__time">{{ $track->formatted_duration }}</span>
                            @if($trackPlayCount > 0)
                                <span class="badge">{{ $trackPlayCount }}×</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-layout.notification />

</x-layouts.app>
