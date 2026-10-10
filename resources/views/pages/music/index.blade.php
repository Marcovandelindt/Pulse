<x-layouts.app title="Music">

    <div x-data="{
        playlistOpen: {{ $errors->isNotEmpty() || old('period_type') ? 'true' : 'false' }},
        period: '{{ old('period_type', 'last_30_days') }}',
        year: {{ old('year', now()->year) }},
        from: '{{ old('from', '') }}',
        to: '{{ old('to', '') }}',
        limit: {{ old('track_limit', 50) }},
        customName: '{{ old('playlist_name', '') }}',
        get autoName() {
            const y = this.year || {{ now()->year }};
            const names = {
                last_30_days:  'Most played — last 30 days',
                last_6_months: 'Most played — last 6 months',
                this_year:     'Most played — ' + {{ now()->year }},
                specific_year: 'Most played — ' + y,
                custom:        this.from && this.to ? 'Most played — ' + this.from + ' to ' + this.to : 'Most played — custom period',
            };
            return names[this.period] || '';
        },
        get name() { return this.customName || this.autoName; },
        open() { this.customName = ''; this.playlistOpen = true; },
    }" @keydown.escape.window="playlistOpen = false">

    <x-layout.page-header title="Music">
        <x-slot:actions>
            <a href="{{ route('music.stats') }}" class="btn btn--secondary btn--sm">Stats</a>
            <a href="{{ route('music.import.index') }}" class="btn btn--secondary btn--sm">Import</a>
            <button @click="open()" class="btn btn--secondary btn--sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.661a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.661A2.25 2.25 0 0 0 9 15.553Z" />
                </svg>
                Create playlist
            </button>
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

    @if(session('playlist_url'))
        <div class="playlist-created-banner">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.661a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.661A2.25 2.25 0 0 0 9 15.553Z" />
            </svg>
            <span>{{ session('success') }}</span>
            <a href="{{ session('playlist_url') }}" target="_blank" rel="noopener" class="playlist-created-banner__link">
                Open in Spotify →
            </a>
        </div>
    @endif

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
                <div class="now-playing__track">
                    @if($currentlyPlaying['track'] ?? null)
                        <a href="{{ route('music.tracks.show', $currentlyPlaying['track']) }}" class="hover:underline" style="color: inherit;">{{ $currentlyPlaying['track_name'] }}</a>
                    @else
                        {{ $currentlyPlaying['track_name'] }}
                    @endif
                </div>
                <div class="now-playing__artist">
                    @foreach($currentlyPlaying['artists'] as $artist)
                        @php $artistModel = $currentlyPlaying['artist_models'][$artist['spotify_artist_id']] ?? null @endphp
                        @if($artistModel)
                            <a href="{{ route('music.artists.show', $artistModel) }}" class="hover:underline" style="color: inherit;">{{ $artist['name'] }}</a>
                        @else
                            {{ $artist['name'] }}
                        @endif
                        @if(!$loop->last)<span> · </span>@endif
                    @endforeach
                    <span> · {{ $currentlyPlaying['album_name'] }}</span>
                </div>
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


                @if($recentPlays->isEmpty())
                    <x-ui.empty-state title="No plays yet" description="Connect Spotify and sync to see your listening history." />
                @else
                    <div class="play-list">
                        @foreach($recentPlays as $play)
                            <div class="play-item">
                                @if($play->track?->album?->image_url)
                                    <img src="{{ $play->track->album->image_url }}" alt="{{ $play->track->album->name }}" class="play-item__cover {{ $play->track->is_obsession ? 'play-item__cover--obsession' : '' }}">
                                @else
                                    <div class="play-item__cover {{ $play->track?->is_obsession ? 'play-item__cover--obsession' : '' }}"></div>
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
                                    @php $linkedGame = $play->track?->gameable_id ? ($gameMap[$play->track->gameable_type.':'.$play->track->gameable_id] ?? null) : null @endphp
                                    @if($linkedGame)
                                        <div class="play-item__game-link">
                                            @if($linkedGame->image_url)
                                                <img src="{{ $linkedGame->image_url }}" alt="" class="play-item__game-cover">
                                            @endif
                                            From {{ $linkedGame->display_name ?? $linkedGame->name }}
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
                                    @php $linkedGame = $track->gameable_id ? ($gameMap[$track->gameable_type.':'.$track->gameable_id] ?? null) : null @endphp
                                    @if($linkedGame)
                                        <div class="play-item__game-link">
                                            @if($linkedGame->image_url)
                                                <img src="{{ $linkedGame->image_url }}" alt="" class="play-item__game-cover">
                                            @endif
                                            From {{ $linkedGame->display_name ?? $linkedGame->name }}
                                        </div>
                                    @endif
                                </div>
                                <span class="play-item__time">{{ $track->plays_since_obsession }}×</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

        </div>

    </div>

    {{-- Playlists --}}
    @if($playlists->isNotEmpty())
        <div class="music-playlists">
            <div class="music-header" style="margin-top: 2rem;">
                <h2 class="music-header__title">Created playlists</h2>
            </div>
            <div class="playlist-list">
                @foreach($playlists as $playlist)
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
                    <div class="playlist-row">
                        <div class="playlist-row__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.661a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.661A2.25 2.25 0 0 0 9 15.553Z" />
                            </svg>
                        </div>
                        <div class="playlist-row__info">
                            <a href="{{ route('music.playlists.show', $playlist) }}" class="playlist-row__name">{{ $playlist->name }}</a>
                            <div class="playlist-row__meta">{{ $periodLabel }} · {{ $playlist->track_count }} tracks · created {{ $playlist->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="playlist-row__actions">
                            <a href="{{ $playlist->url }}" target="_blank" rel="noopener" class="btn btn--secondary btn--sm">
                                Open in Spotify
                            </a>
                            <form method="POST" action="{{ route('music.playlists.destroy', $playlist) }}" onsubmit="return confirm('Delete \'{{ addslashes($playlist->name) }}\' from Pulse and Spotify?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Create playlist modal --}}
    <div x-show="playlistOpen" x-cloak class="playlist-modal" @click.self="playlistOpen = false">
        <div class="playlist-modal__panel">
            <div class="playlist-modal__header">
                <span class="playlist-modal__title">Create Spotify playlist</span>
                <button class="playlist-modal__close" @click="playlistOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('music.playlists.store') }}" class="playlist-modal__body">
                @csrf

                {{-- Period --}}
                <div class="playlist-field">
                    <label class="playlist-field__label">Period</label>
                    <div class="playlist-period-grid">
                        @foreach([
                            'last_30_days'  => 'Last 30 days',
                            'last_6_months' => 'Last 6 months',
                            'this_year'     => 'This year',
                            'specific_year' => 'Specific year',
                            'custom'        => 'Custom range',
                        ] as $value => $label)
                            <label class="playlist-period-btn" :class="{ 'playlist-period-btn--active': period === '{{ $value }}' }">
                                <input type="radio" name="period_type" value="{{ $value }}" x-model="period" class="sr-only">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('period_type') <span class="playlist-field__error">{{ $message }}</span> @enderror
                </div>

                {{-- Year (conditional) --}}
                <div x-show="period === 'specific_year'" x-cloak class="playlist-field">
                    <label class="playlist-field__label" for="pl-year">Year</label>
                    <input
                        id="pl-year"
                        type="number"
                        name="year"
                        x-model="year"
                        class="form-input"
                        min="2000"
                        max="{{ now()->year }}"
                        placeholder="{{ now()->year }}"
                    >
                    @error('year') <span class="playlist-field__error">{{ $message }}</span> @enderror
                </div>

                {{-- Custom range (conditional) --}}
                <div x-show="period === 'custom'" x-cloak class="playlist-field">
                    <label class="playlist-field__label">Date range</label>
                    <div class="playlist-date-row">
                        <div>
                            <label class="playlist-field__sublabel" for="pl-from">From</label>
                            <input id="pl-from" type="date" name="from" x-model="from" class="form-input" max="{{ now()->toDateString() }}">
                            @error('from') <span class="playlist-field__error">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="playlist-field__sublabel" for="pl-to">To</label>
                            <input id="pl-to" type="date" name="to" x-model="to" class="form-input" max="{{ now()->toDateString() }}">
                            @error('to') <span class="playlist-field__error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Track limit --}}
                <div class="playlist-field">
                    <label class="playlist-field__label" for="pl-limit">
                        Tracks
                        <span class="playlist-field__count" x-text="limit"></span>
                    </label>
                    <input
                        id="pl-limit"
                        type="range"
                        name="track_limit"
                        x-model="limit"
                        min="5"
                        max="100"
                        step="5"
                        class="playlist-range"
                    >
                    <div class="playlist-range-labels">
                        <span>5</span><span>100</span>
                    </div>
                    @error('track_limit') <span class="playlist-field__error">{{ $message }}</span> @enderror
                </div>

                {{-- Playlist name --}}
                <div class="playlist-field">
                    <label class="playlist-field__label" for="pl-name">Playlist name</label>
                    <input
                        id="pl-name"
                        type="text"
                        name="playlist_name"
                        :value="name"
                        @input="customName = $event.target.value"
                        class="form-input"
                        maxlength="100"
                        required
                    >
                    @error('playlist_name') <span class="playlist-field__error">{{ $message }}</span> @enderror
                </div>

                <p class="playlist-modal__note">The playlist will be created as private on your Spotify account.</p>

                <div class="playlist-modal__footer">
                    <button type="button" class="btn btn--secondary btn--sm" @click="playlistOpen = false">Cancel</button>
                    <button type="submit" class="btn btn--primary btn--sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.661a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.661A2.25 2.25 0 0 0 9 15.553Z" />
                        </svg>
                        Create playlist
                    </button>
                </div>
            </form>
        </div>
    </div>

    </div>{{-- end x-data --}}

    <x-layout.notification />

</x-layouts.app>
