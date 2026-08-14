<x-layouts.app :title="$track->title">

    {{-- Hero backdrop: primary artist photo --}}
    @if($heroImage)
        <div class="media-hero media-hero--blur" style="--hero-img: url('{{ $heroImage }}')">
            <div class="media-hero__overlay"></div>
        </div>
    @endif

    <div class="media-detail">

        <div class="media-detail__main">

            @if($track->album?->image_url)
                <div class="media-detail__poster-wrap">
                    <img src="{{ $track->album->image_url }}" alt="{{ $track->album->name }}" class="media-detail__poster">
                </div>
            @endif

            <div class="media-detail__info">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="media-detail__title">{{ $track->title }}</h1>
                        <div class="media-detail__original-title">
                            @foreach($track->artists as $artist)
                                <a href="{{ route('music.artists.show', $artist) }}" class="hover:underline" style="color: inherit;">{{ $artist->name }}</a>
                                @if(! $loop->last)<span> · </span>@endif
                            @endforeach
                            @if($track->album)
                                <span> · </span>
                                <a href="{{ route('music.albums.show', $track->album) }}" class="hover:underline" style="color: inherit;">{{ $track->album->name }}</a>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
                </div>

                <div class="media-detail__meta-row">
                    @if($track->is_explicit)
                        <span class="explicit-badge">E</span>
                    @endif
                    @if($track->formatted_duration)
                        <span>{{ $track->formatted_duration }}</span>
                    @endif
                    @if($track->album?->release_year)
                        <span>{{ $track->album->release_year }}</span>
                    @endif
                </div>

                <div class="media-detail__stats-row">
                    <div class="media-detail__stat">
                        <div class="media-detail__stat-value">{{ number_format($playCount) }}</div>
                        <div class="media-detail__stat-label">total plays</div>
                    </div>
                    @if($firstPlay)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ $firstPlay->played_at->format('d M Y') }}</div>
                            <div class="media-detail__stat-label">first played</div>
                        </div>
                    @endif
                    @if($lastPlay)
                        <div class="media-detail__stat">
                            <div class="media-detail__stat-value">{{ $lastPlay->played_at->format('d M Y') }}</div>
                            <div class="media-detail__stat-label">last played</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <x-ui.card title="Play history" class="mt-6">
            @if($track->plays->isEmpty())
                <x-ui.empty-state title="No plays recorded" />
            @else
                <div class="play-list">
                    @foreach($track->plays->sortByDesc('played_at') as $play)
                        <div class="play-item">
                            <div class="play-item__info">
                                <div class="play-item__title">{{ $play->played_at->format('D, d M Y') }}</div>
                                <div class="play-item__meta">{{ $play->played_at->format('H:i') }}</div>
                            </div>
                            <span class="play-item__time">{{ $play->played_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>

    <x-layout.notification />

</x-layouts.app>
