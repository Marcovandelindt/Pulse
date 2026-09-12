@php
    $links = [
        ['route' => 'health.index',    'label' => 'Overview'],
        ['route' => 'health.sleep',    'label' => 'Sleep'],
        ['route' => 'health.activity', 'label' => 'Activity'],
        ['route' => 'health.vitals',   'label' => 'Vitals'],
        ['route' => 'health.mobility', 'label' => 'Mobility'],
        ['route' => 'health.hearing',  'label' => 'Hearing'],
        ['route' => 'health.stats',    'label' => 'Stats'],
    ];
@endphp

<nav class="health-subnav">
    @foreach ($links as $link)
        <a href="{{ route($link['route']) }}"
           class="health-subnav__item {{ request()->routeIs($link['route']) ? 'health-subnav__item--active' : '' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
