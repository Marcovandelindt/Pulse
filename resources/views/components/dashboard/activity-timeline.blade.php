@props(['activities' => collect()])

<div class="activity-timeline">
    @forelse($activities as $activity)
        <div class="activity-timeline__item activity-timeline__item--{{ str_replace('_', '-', $activity->type) }}{{ $activity->isPinned ? ' activity-timeline__item--pinned' : '' }}">
            <div class="activity-timeline__connector">
                <div class="activity-timeline__dot"></div>
                @unless($loop->last)
                    <div class="activity-timeline__line"></div>
                @endunless
            </div>

            <div class="activity-timeline__body">
                @if($activity->imageUrl)
                    <img
                        src="{{ $activity->imageUrl }}"
                        alt="{{ $activity->title }}"
                        class="activity-timeline__poster"
                        loading="lazy"
                    >
                @else
                    <div class="activity-timeline__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                @endif

                <div class="activity-timeline__info">
                    <div class="activity-timeline__title">{{ $activity->title }}</div>
                    <div class="activity-timeline__subtitle">{{ $activity->subtitle }}</div>
                </div>

                <time
                    class="activity-timeline__time"
                    datetime="{{ $activity->occurredAt->toIso8601String() }}"
                >
                    {{ $activity->occurredAt->year === now()->year
                        ? $activity->occurredAt->format('d M')
                        : $activity->occurredAt->format('d M Y') }}
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
