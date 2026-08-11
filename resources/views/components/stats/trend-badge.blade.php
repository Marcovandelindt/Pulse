@props(['trend' => 0, 'label' => null])

@php
    $isUp   = $trend > 0;
    $isDown = $trend < 0;
    $color  = $isUp ? 'text-green-400' : ($isDown ? 'text-red-400' : 'text-gray-400');
@endphp

<span class="inline-flex items-center gap-1 text-xs font-medium {{ $color }}">
    @if($isUp)
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
    @elseif($isDown)
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 10a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1z" clip-rule="evenodd" />
        </svg>
    @endif
    {{ abs($trend) }}%
    @if($label)
        <span class="text-[var(--color-text-muted)]">{{ $label }}</span>
    @endif
</span>
