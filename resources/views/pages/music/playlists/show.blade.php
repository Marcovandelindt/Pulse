<x-layouts.app :title="$playlist->name">

    <x-layout.page-header :title="$playlist->name">
        <x-slot:actions>
            <a href="{{ $playlist->url }}" target="_blank" rel="noopener" class="btn btn--secondary btn--sm">Open in Spotify</a>
            <form method="POST" action="{{ route('music.playlists.destroy', $playlist) }}" onsubmit="return confirm('Delete \'{{ addslashes($playlist->name) }}\' from Pulse and Spotify?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Delete playlist</button>
            </form>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    @php
        $periodLabel = match($playlist->period_type) {
            'last_30_days'  => 'Last 30 days',
            'last_6_months' => 'Last 6 months',
            'this_year'     => 'This year',
            'specific_year' => $playlist->period_from?->format('Y') ?? '—',
            'custom'        => ($playlist->period_from?->format('M j, Y') ?? '—').' – '.($playlist->period_to?->format('M j, Y') ?? '—'),
            default         => '—',
        };
    @endphp

    <div class="playlist-detail-meta">
        <span class="badge badge--muted">{{ $periodLabel }}</span>
        <span class="playlist-detail-meta__sep">·</span>
        <span class="playlist-detail-meta__text">{{ $playlist->track_count }} tracks</span>
        <span class="playlist-detail-meta__sep">·</span>
        <span class="playlist-detail-meta__text">Created {{ $playlist->created_at->format('M j, Y') }}</span>
    </div>

    <x-ui.card>
        @if($playlist->tracks->isEmpty())
            <x-ui.empty-state title="No tracks" description="Track data was not saved for this playlist." />
        @else
            <div class="play-list">
                @foreach($playlist->tracks as $track)
                    <div class="play-item">
                        <span class="playlist-track-pos">{{ $loop->iteration }}</span>
                        @if($track->album?->image_url)
                            <img src="{{ $track->album->image_url }}" alt="{{ $track->album?->name }}" class="play-item__cover">
                        @else
                            <div class="play-item__cover"></div>
                        @endif
                        <div class="play-item__info">
                            <a href="{{ route('music.tracks.show', $track) }}" class="play-item__title">{{ $track->title }}</a>
                            <div class="play-item__meta">
                                @foreach($track->artists as $artist)
                                    <a href="{{ route('music.artists.show', $artist) }}" class="hover:underline">{{ $artist->name }}</a>
                                    @if(!$loop->last)<span> · </span>@endif
                                @endforeach
                                @if($track->album)
                                    · <a href="{{ route('music.albums.show', $track->album) }}" class="hover:underline">{{ $track->album->name }}</a>
                                @endif
                            </div>
                        </div>
                        <div class="playlist-track-plays">
                            <span class="playlist-track-plays__count">{{ $track->pivot->play_count }}</span>
                            <span class="playlist-track-plays__label">plays</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

</x-layouts.app>
