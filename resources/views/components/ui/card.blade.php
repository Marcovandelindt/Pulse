@props([
    'title'   => null,
    'compact' => false,
    'class'   => '',
])

<div {{ $attributes->class(['card', 'card--compact' => $compact, $class]) }}>
    @if($title)
        <div class="card__header">
            <span class="card__header-title">{{ $title }}</span>
            @isset($action)
                <div>{{ $action }}</div>
            @endisset
        </div>
    @endif

    <div class="card__body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card__footer">{{ $footer }}</div>
    @endisset
</div>
