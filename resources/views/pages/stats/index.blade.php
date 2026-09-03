<x-layouts.app title="Stats">

    <x-layout.page-header
        title="Stats"
        subtitle="A year of activity at a glance."
    />

    <div class="stats-heatmaps">

        <x-stats.heatmap
            label="Steps"
            :entries="$stepsData"
            unit="steps"
            scheme="green"
        />

        <x-stats.heatmap
            label="Gaming"
            :entries="$gamingData"
            unit="minutes"
            scheme="purple"
            :format="fn($v) => ($v >= 60 ? intdiv($v, 60) . 'h ' . ($v % 60) . 'm' : $v . 'm') . ' played'"
        />

        <x-stats.heatmap
            label="Music"
            :entries="$musicData"
            unit="tracks"
            scheme="pink"
        />

        <x-stats.heatmap
            label="Media"
            :entries="$mediaData"
            unit="episodes / films"
            scheme="amber"
        />

    </div>

</x-layouts.app>
