@props(['name' => 'U', 'size' => 'md'])

@php
    $initials = collect(explode(' ', $name))
        ->take(2)
        ->map(fn($word) => strtoupper($word[0]))
        ->join('');

    $sizeClass = match($size) {
        'sm' => 'w-7 h-7 text-xs',
        'lg' => 'w-12 h-12 text-base',
        default => 'w-9 h-9 text-sm',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center justify-center rounded-full font-semibold bg-[var(--color-brand)] text-white {$sizeClass}"]) }}>
    {{ $initials }}
</span>
