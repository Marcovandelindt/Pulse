@props([
    'label'      => '',
    'value'      => '—',
    'icon'       => null,
    'iconColor'  => 'brand',
    'trend'      => null,
    'trendLabel' => null,
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
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                @endif
            </svg>
        </div>
    @endif

    <div class="stat-card__content">
        <div class="stat-card__label">{{ $label }}</div>
        <div class="stat-card__value">{{ $value }}</div>

        @if($trend !== null)
            <div class="stat-card__trend">
                <x-stats.trend-badge :trend="$trend" :label="$trendLabel" />
            </div>
        @endif
    </div>
</div>
