<x-layouts.app title="Dashboard">

    <x-layout.page-header
        title="Good morning, Marco"
        subtitle="Here's what's happening today."
    />

    {{-- Stats row --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Steps this week"
            :value="$stepsThisWeek ?? '—'"
            icon="heart"
        />
        <x-stats.stat-card
            label="Sleep"
            value="—"
        />
        <x-stats.stat-card
            label="Expenses this month"
            value="—"
            icon="credit-card"
        />
        <x-stats.stat-card
            label="Now playing"
            value="—"
            icon="musical-note"
        />
    </div>

    {{-- Main grid --}}
    <div class="dashboard-grid">
        <x-ui.card title="Recent Activity">
            <x-dashboard.activity-timeline :activities="$timeline" />
        </x-ui.card>

        <div class="flex flex-col gap-6">
            <x-ui.card title="Now listening" class="card--flush">
                @if($currentlyPlaying)
                    @php $albumBg = $currentlyPlaying['album_image_url'] ?? null; @endphp
                    <div class="now-listening {{ $albumBg ? '' : 'now-listening--no-image' }}"
                         @if($albumBg) style="--album-bg: url('{{ $albumBg }}')" @endif>
                        <div class="now-listening__content">
                            @if($albumBg)
                                <img src="{{ $albumBg }}" alt="" class="now-listening__cover">
                            @endif
                            <div class="now-listening__info">
                                <div class="now-listening__track">{{ $currentlyPlaying['track_name'] }}</div>
                                <div class="now-listening__artist">{{ $currentlyPlaying['artist_names'] }}</div>
                                <div class="now-listening__meta">
                                    <div class="now-listening__bars">
                                        <div class="now-listening__bar"></div>
                                        <div class="now-listening__bar"></div>
                                        <div class="now-listening__bar"></div>
                                        <div class="now-listening__bar"></div>
                                    </div>
                                    <span class="now-listening__label">Now playing</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($recentPlay)
                    @php $albumBg = $recentPlay->track->album?->image_url; @endphp
                    <div class="now-listening {{ $albumBg ? '' : 'now-listening--no-image' }}"
                         @if($albumBg) style="--album-bg: url('{{ $albumBg }}')" @endif>
                        <div class="now-listening__content">
                            @if($albumBg)
                                <img src="{{ $albumBg }}" alt="" class="now-listening__cover">
                            @endif
                            <div class="now-listening__info">
                                <div class="now-listening__track">{{ $recentPlay->track->title }}</div>
                                <div class="now-listening__artist">{{ $recentPlay->track->artists_string }}</div>
                                <div class="now-listening__meta">
                                    <span class="now-listening__label">{{ $recentPlay->played_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="now-listening now-listening--no-image">
                        <div class="now-listening__content">
                            <div class="now-listening__info">
                                <div class="now-listening__track" style="color: rgba(255,255,255,0.4)">Nothing playing</div>
                            </div>
                        </div>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Recent expenses">
                <x-ui.empty-state title="No expenses yet" />
            </x-ui.card>
        </div>
    </div>

    <x-layout.notification />

</x-layouts.app>
