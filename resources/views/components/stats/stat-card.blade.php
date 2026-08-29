@props([
    'label'      => '',
    'value'      => '—',
    'icon'       => null,
    'iconColor'  => 'brand',
    'trend'      => null,
    'trendLabel' => null,
    'subtitle'   => null,
])

<div class="stat-card">
    @if($icon)
        <div class="stat-card__icon" style="background: oklch(from var(--color-brand) l c h / 0.15);">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-brand-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                @if($icon === 'heart')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                @elseif($icon === 'credit-card')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                @elseif($icon === 'musical-note')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                @elseif($icon === 'trophy')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.25 9.71 2 12 2c2.291 0 4.545.25 6.75.721v1.515M18.75 4.236c.982.143 1.954.317 2.916.52a6.003 6.003 0 01-5.395 5.492M18.75 4.236V4.5a6.75 6.75 0 01-2.48 5.228" />
                @elseif($icon === 'film')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                @elseif($icon === 'gamepad')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8a3 3 0 013-3h8a3 3 0 013 3v8a3 3 0 01-3 3H8a3 3 0 01-3-3V8zM9 10v4M7 12h4M15 10h.01M15 14h.01M13 12h.01M17 12h.01" />
                @elseif($icon === 'clock')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                @elseif($icon === 'play')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                @endif
            </svg>
        </div>
    @endif

    <div class="stat-card__content">
        <div class="stat-card__label">{{ $label }}</div>
        <div class="stat-card__value">{{ $value }}</div>

        @if($subtitle !== null)
            <div class="stat-card__subtitle">{{ $subtitle }}</div>
        @endif

        @if($trend !== null)
            <div class="stat-card__trend">
                <x-stats.trend-badge :trend="$trend" :label="$trendLabel" />
            </div>
        @endif
    </div>
</div>
