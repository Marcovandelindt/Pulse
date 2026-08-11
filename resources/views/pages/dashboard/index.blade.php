<x-layouts.app title="Dashboard">

    <x-layout.page-header
        title="Good morning, Marco"
        subtitle="Here's what's happening today."
    />

    {{-- Stats row --}}
    <div class="stats-row">
        <x-stats.stat-card
            label="Steps today"
            value="—"
            icon="heart"
        />
        <x-stats.stat-card
            label="Sleep"
            value="—"
        />
        <x-stats.stat-card
            label="Expenses this month"
            value="—"
            icon="credit-card"
        />
        <x-stats.stat-card
            label="Now playing"
            value="—"
            icon="musical-note"
        />
    </div>

    {{-- Main grid --}}
    <div class="dashboard-grid">
        <x-ui.card title="Activity">
            <x-ui.empty-state
                title="No activity data yet"
                description="Start logging your health data to see trends here."
            />
        </x-ui.card>

        <div class="flex flex-col gap-6">
            <x-ui.card title="Now listening">
                <x-ui.empty-state title="Nothing playing" />
            </x-ui.card>

            <x-ui.card title="Recent expenses">
                <x-ui.empty-state title="No expenses yet" />
            </x-ui.card>
        </div>
    </div>

    <x-layout.notification />

</x-layouts.app>
