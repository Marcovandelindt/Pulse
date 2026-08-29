<x-layouts.app title="Music Stats {{ $year }}">

    <x-layout.page-header title="Music Stats {{ $year }}">
        <x-slot:actions>
            <a href="{{ route('music.index') }}" class="btn btn--secondary btn--sm">&larr; Music</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="stats-row">
        <x-stats.stat-card label="Plays this year"    :value="number_format($totalPlays)" />
        <x-stats.stat-card label="Hours listened"     :value="number_format((int) round($totalMinutes / 60))" />
        <x-stats.stat-card label="Unique tracks"      :value="number_format($uniqueTracks)" />
        <x-stats.stat-card label="Unique artists"     :value="number_format($uniqueArtists)" />
        <x-stats.stat-card label="Unique albums"      :value="number_format($uniqueAlbums)" />
        <x-stats.stat-card label="Avg plays / day"    :value="number_format($avgPlaysPerDay, 1)" />
    </div>

    <div class="mt-6">
        <x-ui.card title="Plays per month — {{ $year }}">
            @if($playsPerMonth->sum('count') === 0)
                <x-ui.empty-state title="No data yet" />
            @else
                <canvas
                    data-chart="bar"
                    data-chart-data="{{ json_encode([
                        'labels' => $playsPerMonth->pluck('label')->toArray(),
                        'values' => $playsPerMonth->pluck('count')->toArray(),
                    ]) }}"
                    style="height: 200px;"
                ></canvas>
            @endif
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 mt-6 lg:grid-cols-3">

        <x-ui.card title="Top tracks">
            @if($topTracks->isEmpty())
                <x-ui.empty-state title="No data" />
            @else
                <div class="play-list">
                    @foreach($topTracks as $row)
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

        <x-ui.card title="Top artists">
            @if($topArtists->isEmpty())
                <x-ui.empty-state title="No data" />
            @else
                <div class="play-list">
                    @foreach($topArtists as $artist)
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

        <x-ui.card title="Top albums">
            @if($topAlbums->isEmpty())
                <x-ui.empty-state title="No data" />
            @else
                <div class="play-list">
                    @foreach($topAlbums as $album)
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
                                <div class="play-item__meta">{{ $album->release_year }}</div>
                            </div>
                            <span class="play-item__time">{{ $album->play_count }}×</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>

    <x-layout.notification />

</x-layouts.app>
