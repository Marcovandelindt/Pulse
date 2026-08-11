@props(['title', 'subtitle' => null])

<div class="page-header">
    <h1 class="page-header__title">{{ $title }}</h1>
    @if($subtitle)
        <p class="page-header__subtitle">{{ $subtitle }}</p>
    @endif
    @isset($actions)
        <div class="flex gap-2 mt-3">{{ $actions }}</div>
    @endisset
</div>
