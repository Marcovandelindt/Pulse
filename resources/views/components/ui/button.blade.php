@props([
    'variant' => 'primary',
    'size'    => null,
    'type'    => 'button',
    'href'    => null,
])

@php
    $tag     = $href ? 'a' : 'button';
    $classes = implode(' ', array_filter([
        'btn',
        "btn--{$variant}",
        $size ? "btn--{$size}" : null,
    ]));
@endphp

<{{ $tag }}
    {{ $href ? "href={$href}" : "type={$type}" }}
    {{ $attributes->class([$classes]) }}
>{{ $slot }}</{{ $tag }}>
