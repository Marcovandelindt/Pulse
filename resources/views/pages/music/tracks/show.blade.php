<x-layouts.app :title="$track->title">

    <x-layout.page-header :title="$track->title">
        <x-slot:actions>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="track-detail">
        @if($track->album?->image_url)
            <img src="{{ $track->album->image_url }}" alt="{{ $track->album->name }}" class="track-detail__cover">
        @endif
        <div class="track-detail__info">
            <h2 class="track-detail__title">{{ $track->title }}</h2>
            <div class="track-detail__meta">
                @foreach($track->artists as $artist)
                    <a href="{{ route('music.artists.show', $artist) }}" class="hover:underline text-[var(--color-text-primary)]">{{ $artist->name }}</a>
                    @if(! $loop->last)<span>·</span>@endif
                @endforeach
                @if($track->album)
                    <span>·</span>
                    <a href="{{ route('music.albums.show', $track->album) }}" class="hover:underline">{{ $track->album->name }}</a>
                @endif
                @if($track->is_explicit)
                    <span class="explicit-badge">E</span>
                @endif
            </div>

            <div class="flex flex-wrap gap-4 mt-4">
                <div>
                    <div class="text-xs text-[var(--color-text-muted)] uppercase tracking-wide">Duration</div>
                    <div class="text-sm font-medium text-[var(--color-text-primary)] mt-0.5">{{ $track->formatted_duration }}</div>
                </div>
                <div>
                    <div class="text-xs text-[var(--color-text-muted)] uppercase tracking-wide">Total plays</div>
                    <div class="text-sm font-medium text-[var(--color-text-primary)] mt-0.5">{{ number_format($playCount) }}</div>
                </div>
                @if($firstPlay)
                    <div>
                        <div class="text-xs text-[var(--color-text-muted)] uppercase tracking-wide">First played</div>
                        <div class="text-sm font-medium text-[var(--color-text-primary)] mt-0.5">{{ $firstPlay->played_at->format('d M Y') }}</div>
                    </div>
                @endif
                @if($lastPlay)
                    <div>
                        <div class="text-xs text-[var(--color-text-muted)] uppercase tracking-wide">Last played</div>
                        <div class="text-sm font-medium text-[var(--color-text-primary)] mt-0.5">{{ $lastPlay->played_at->diffForHumans() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-ui.card title="Play history">
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

    <x-layout.notification />

</x-layouts.app>
