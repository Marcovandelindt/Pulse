<x-layouts.app title="Waze Heatmap">

    @push('head')
        @vite('resources/js/modules/waze-heatmap.js')
    @endpush

    <x-layout.page-header title="Drive Heatmap">
        <x-slot:actions>
            <a href="{{ route('waze.index') }}" class="btn btn--secondary btn--sm">← Drives</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="waze-heatmap-wrap">
        <div
            id="waze-heatmap"
            data-url="{{ route('waze.heatmap.data') }}"
            class="waze-heatmap"
        ></div>
    </div>

</x-layouts.app>
