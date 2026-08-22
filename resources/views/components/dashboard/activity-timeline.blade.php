@props(['activities' => collect()])

<div class="activity-timeline">
    @forelse($activities as $activity)
        <div
            class="activity-timeline__item activity-timeline__item--{{ str_replace('_', '-', $activity->type) }}{{ $activity->isPinned ? ' activity-timeline__item--pinned' : '' }}"
            @if(count($activity->episodes) > 0) x-data="{ open: false }" @endif
        >
            <div class="activity-timeline__connector">
                <div class="activity-timeline__dot"></div>
                <div class="activity-timeline__line {{ $loop->last ? 'activity-timeline__line--hidden' : '' }}"></div>
            </div>

            <div class="activity-timeline__body">
                @if($activity->imageUrl)
                    @if($activity->url)
                        <a href="{{ $activity->url }}" class="activity-timeline__poster-wrap">
                            <img src="{{ $activity->imageUrl }}" alt="{{ $activity->title }}" class="activity-timeline__poster" loading="lazy">
                        </a>
                    @else
                        <div class="activity-timeline__poster-wrap">
                            <img src="{{ $activity->imageUrl }}" alt="{{ $activity->title }}" class="activity-timeline__poster" loading="lazy">
                        </div>
                    @endif
                @elseif($activity->type === 'music')
                    <div class="activity-timeline__icon activity-timeline__icon--music">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                    </div>
                @elseif($activity->type === 'playstation')
                    <div class="activity-timeline__icon activity-timeline__icon--playstation">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h2M9 11v2M14.5 12h.01M16 12h.01M7.5 8.5h9a4.5 4.5 0 014.5 4.5V14a4.5 4.5 0 01-4.5 4.5h-9A4.5 4.5 0 013 14v-1a4.5 4.5 0 014.5-4.5z" />
                        </svg>
                    </div>
                @else
                    <div class="activity-timeline__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                @endif

                <div class="activity-timeline__info">
                    <div class="activity-timeline__title">
                        @if($activity->url)
                            <a href="{{ $activity->url }}" class="activity-timeline__title-link">{{ $activity->title }}</a>
                        @else
                            {{ $activity->title }}
                        @endif
                    </div>
                    <div class="activity-timeline__subtitle">
                        {{ $activity->subtitle }}
                        @if(count($activity->episodes) > 0)
                            <button
                                @click="open = !open"
                                class="activity-timeline__expand-btn"
                                x-text="open ? '▲' : '▼'"
                                :aria-expanded="open"
                                type="button"
                            ></button>
                        @endif
                    </div>
                    @if(count($activity->episodes) > 0)
                        <ul class="activity-timeline__episode-list" x-show="open" x-collapse style="display:none;">
                            @foreach($activity->episodes as $ep)
                                <li>{{ $ep }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <time
                    class="activity-timeline__time"
                    datetime="{{ $activity->occurredAt->toIso8601String() }}"
                >
                    {{ $activity->occurredAt->year === now()->year
                        ? $activity->occurredAt->format('l, d M')
                        : $activity->occurredAt->format('l, d M Y') }}
                </time>
            </div>
        </div>
    @empty
        <x-ui.empty-state
            title="No activity yet"
            description="Watched episodes and daily steps will appear here."
        />
    @endforelse
</div>
