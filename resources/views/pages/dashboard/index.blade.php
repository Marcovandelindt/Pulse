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
                    <div class="now-listening now-listening--playing">
                        @if($currentlyPlaying['album_image_url'])
                            <img src="{{ $currentlyPlaying['album_image_url'] }}" alt="" class="now-listening__cover">
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
                @elseif($recentPlay)
                    <div class="now-listening">
                        @if($recentPlay->track->album?->image_url)
                            <img src="{{ $recentPlay->track->album->image_url }}" alt="" class="now-listening__cover">
                        @endif
                        <div class="now-listening__info">
                            <div class="now-listening__track">{{ $recentPlay->track->title }}</div>
                            <div class="now-listening__artist">{{ $recentPlay->track->artists_string }}</div>
                            <div class="now-listening__meta">
                                <span class="now-listening__label">{{ $recentPlay->played_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <x-ui.empty-state title="Nothing playing" />
                @endif
            </x-ui.card>

            <x-ui.card title="Recent expenses">
                <x-ui.empty-state title="No expenses yet" />
            </x-ui.card>
        </div>
    </div>

    <x-layout.notification />

</x-layouts.app>
