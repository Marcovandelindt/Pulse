<x-layouts.app title="Steam">

<x-layout.page-header title="Steam">
    <x-slot:actions>
        @if($accounts->count() > 1)
            <div class="flex items-center gap-1">
                @foreach($accounts as $account)
                    @if($account->is_active)
                        <span class="btn btn--primary btn--sm">{{ $account->label }}</span>
                    @else
                        <form method="POST" action="{{ route('steam.accounts.activate', $account) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn--secondary btn--sm">{{ $account->label }}</button>
                        </form>
                    @endif
                @endforeach
            </div>
        @endif
        <a href="{{ route('steam.settings') }}" class="btn btn--secondary btn--sm">⚙ Settings</a>
        @if($activeAccount)
            <form method="POST" action="{{ route('steam.sync') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn--primary btn--sm">↻ Sync</button>
            </form>
        @endif
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25);">
        {{ session('error') }}
    </div>
@endif

@if(! $activeAccount)
    <x-ui.empty-state title="No Steam account configured" description="Add a Steam account in Settings to get started.">
        <x-slot:action>
            <a href="{{ route('steam.settings') }}" class="btn btn--primary btn--sm">⚙ Go to Settings</a>
        </x-slot:action>
    </x-ui.empty-state>
@else

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
    <x-stats.stat-card label="Total Games"    :value="number_format($totalGames)"                       icon="play" />
    <x-stats.stat-card label="Total Spent"    :value="'€ ' . number_format($totalSpent, 2, ',', '.')"  icon="credit-card" />
    <x-stats.stat-card label="Total Hours"    :value="number_format($totalHours, 1) . 'h'"              icon="clock" />
    <x-stats.stat-card label="Last 2 Weeks"   :value="number_format($recentHours, 1) . 'h'"             icon="clock" />
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">

    <x-ui.card title="Most Played">
        @if($mostPlayed->isEmpty())
            <p class="text-sm" style="color: var(--color-text-muted)">No games yet. Sync your library first.</p>
        @else
            <div class="space-y-3">
                @foreach($mostPlayed as $game)
                    <div class="flex items-center gap-3">
                        <div class="gaming-icon {{ $game->image_url ? '' : 'gaming-icon--empty' }}">
                            @if($game->image_url)
                                <img src="{{ $game->image_url }}" alt="{{ $game->name }}">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('steam.games.show', $game) }}"
                               class="text-sm font-medium truncate block"
                               style="color: var(--color-text-primary); text-decoration: none;">
                                {{ $game->name }}
                            </a>
                        </div>
                        <span class="text-sm flex-shrink-0" style="color: var(--color-text-muted)">
                            {{ $game->formatted_playtime }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Recently Played">
        @if($recentlyPlayed->isEmpty())
            <p class="text-sm" style="color: var(--color-text-muted)">No recent activity.</p>
        @else
            <div class="space-y-3">
                @foreach($recentlyPlayed as $game)
                    <div class="flex items-center gap-3">
                        <div class="gaming-icon {{ $game->image_url ? '' : 'gaming-icon--empty' }}">
                            @if($game->image_url)
                                <img src="{{ $game->image_url }}" alt="{{ $game->name }}">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('steam.games.show', $game) }}"
                               class="text-sm font-medium truncate block"
                               style="color: var(--color-text-primary); text-decoration: none;">
                                {{ $game->name }}
                            </a>
                            <span class="text-xs" style="color: var(--color-text-muted)">
                                {{ $game->last_played_at?->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Library">
        <div class="space-y-2">
            @foreach(\App\Enums\BacklogStatus::cases() as $bs)
                @php $count = \App\Models\SteamGame::where('steam_account_id', $activeAccount->id)->where('backlog_status', $bs->value)->count(); @endphp
                <div class="flex items-center justify-between text-sm">
                    <span style="color: var(--color-text-muted)">{{ $bs->icon() }} {{ $bs->label() }}</span>
                    <span class="font-medium" style="color: var(--color-text-primary)">{{ $count }}</span>
                </div>
            @endforeach
            @php $unset = \App\Models\SteamGame::where('steam_account_id', $activeAccount->id)->whereNull('backlog_status')->count(); @endphp
            <div class="flex items-center justify-between text-sm">
                <span style="color: var(--color-text-muted)">— Untracked</span>
                <span class="font-medium" style="color: var(--color-text-primary)">{{ $unset }}</span>
            </div>
        </div>
    </x-ui.card>

</div>

<x-ui.card>
    <div class="media-toolbar mb-4">
        <form method="GET" action="{{ route('steam.index') }}" class="flex gap-2 flex-wrap items-center flex-1">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search games…"
                class="form-input"
                style="max-width: 220px;"
            >
            <select name="status" class="form-input" style="max-width: 160px;">
                <option value="">All statuses</option>
                @foreach($backlogStatuses as $bs)
                    <option value="{{ $bs->value }}" {{ $status === $bs->value ? 'selected' : '' }}>
                        {{ $bs->label() }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn--secondary btn--sm">Filter</button>
            @if($search || $status)
                <a href="{{ route('steam.index') }}" class="btn btn--secondary btn--sm">Clear</a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <span class="text-sm" style="color: var(--color-text-muted)">Sort:</span>
            @foreach(['playtime' => 'Playtime', 'playtime_2w' => 'Last 2 Weeks', 'last_played' => 'Last Played', 'name' => 'Name'] as $key => $label)
                <a href="{{ route('steam.index', array_filter(['sort' => $key, 'search' => $search ?: null, 'status' => $status ?: null])) }}"
                   class="btn btn--sm {{ $sort === $key ? 'btn--primary' : 'btn--secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    @if($games->isEmpty())
        <x-ui.empty-state title="No games found" description="Try adjusting your filters or sync your Steam library.">
            <x-slot:action>
                <form method="POST" action="{{ route('steam.sync') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn--primary btn--sm">↻ Sync from Steam</button>
                </form>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>Game</th>
                    <th>Status</th>
                    <th>Playtime</th>
                    <th>Last Played</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                    <tr>
                        <td style="width: 2.5rem;">
                            <div class="gaming-icon {{ $game->image_url ? '' : 'gaming-icon--empty' }}">
                                @if($game->image_url)
                                    <img src="{{ $game->image_url }}" alt="{{ $game->name }}">
                                @endif
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('steam.games.show', $game) }}"
                               class="text-sm font-medium"
                               style="color: var(--color-text-primary); text-decoration: none;">
                                {{ $game->name }}
                            </a>
                        </td>
                        <td>
                            @if($game->backlog_status)
                                <span class="badge">
                                    {{ $game->backlog_status->icon() }} {{ $game->backlog_status->label() }}
                                </span>
                            @else
                                <span class="text-xs" style="color: var(--color-text-muted)">—</span>
                            @endif
                        </td>
                        <td class="text-sm" style="color: var(--color-text-muted)">{{ $game->formatted_playtime }}</td>
                        <td class="text-sm" style="color: var(--color-text-muted)">{{ $game->last_played_at?->format('d M Y') ?? '—' }}</td>
                        <td style="width: 3rem;">
                            <a href="{{ route('steam.games.edit', $game) }}"
                               class="btn btn--secondary btn--sm btn--icon" title="Edit">✎</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $games->links() }}</div>
    @endif
</x-ui.card>

@endif

</x-layouts.app>
