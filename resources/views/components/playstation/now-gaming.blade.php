@props([
    'game',
    'playing'  => true,
    'playedAt' => null,
    'url'      => null,
])

@if($url)
<a href="{{ $url }}" class="now-gaming now-gaming--link {{ $playing ? 'now-gaming--playing' : '' }}">
@else
<div class="now-gaming {{ $playing ? 'now-gaming--playing' : '' }}">
@endif
    @if($game['image_url'])
        <div class="now-gaming__cover-wrap">
            <img src="{{ $game['image_url'] }}" alt="{{ $game['title'] }}">
        </div>
    @endif
    <div class="now-gaming__info">
        <div class="now-gaming__title">{{ $game['title'] }}</div>
        @if($game['platform'])
            <div class="now-gaming__platform">{{ $game['platform'] }}</div>
        @endif
        <div class="now-gaming__meta">
            @if($playing)
                <span class="now-gaming__dot"></span>
                <span class="now-gaming__label">Now playing</span>
            @else
                <span class="now-gaming__label">{{ $playedAt?->diffForHumans() ?? 'Recently' }}</span>
            @endif
        </div>
    </div>
@if($url)
</a>
@else
</div>
@endif
