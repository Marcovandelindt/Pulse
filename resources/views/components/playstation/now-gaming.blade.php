@props([
    'game',
    'playing'   => true,
    'playedAt'  => null,
    'url'       => null,
    'startedAt' => null,
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
                @if($startedAt)
                    <span class="now-gaming__timer-sep">·</span>
                    <span class="now-gaming__label">since {{ $startedAt->format('H:i') }}</span>
                    <span class="now-gaming__timer-sep">·</span>
                    <span class="now-gaming__timer"
                          x-data="{
                              elapsed: Math.floor(Date.now()/1000) - {{ $startedAt->timestamp }},
                              format(s) {
                                  const h = Math.floor(s/3600);
                                  const m = Math.floor((s%3600)/60);
                                  const sec = s%60;
                                  if (h > 0) return h+'h '+m+'m';
                                  if (m > 0) return m+'m';
                                  return sec+'s';
                              }
                          }"
                          x-init="setInterval(() => elapsed++, 1000)"
                          x-text="format(elapsed)"></span>
                @endif
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
