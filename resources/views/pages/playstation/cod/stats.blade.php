<x-layouts.app title="CoD Stats — PlayStation">

    <x-layout.page-header title="Call of Duty — Stats">
        <x-slot:actions>
            <a href="{{ route('playstation.cod.index') }}" class="btn btn--secondary btn--sm">← Matches</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if(! $stats['hasData'])
        <x-ui.empty-state
            title="No stats yet"
            description="Upload and confirm some matches first."
        />
    @else

    {{-- Overview stat cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
        <x-stats.stat-card
            label="Matches"
            :value="number_format($stats['overview']['totalMatches'])"
            icon="play"
        />
        <x-stats.stat-card
            label="Win Rate"
            :value="$stats['overview']['winRate'] . '%'"
            icon="trophy"
        />
        <x-stats.stat-card
            label="Overall K/D"
            :value="$stats['overview']['overallKd']"
            icon="bolt"
        />
        <x-stats.stat-card
            label="Avg Damage"
            :value="number_format($stats['overview']['avgDamage'])"
            icon="fire"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">

        {{-- By map --}}
        <x-ui.card title="By Map">
            @if($stats['byMap']->isEmpty())
                <p class="text-sm" style="color: var(--color-text-muted)">No data.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Map</th>
                                <th class="text-right">Matches</th>
                                <th class="text-right">Win %</th>
                                <th class="text-right">K/D</th>
                                <th class="text-right">Avg DMG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['byMap'] as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-right text-sm" style="color: var(--color-text-muted)">{{ $row['matches'] }}</td>
                                    <td class="text-right">
                                        <span class="{{ $row['winRate'] >= 50 ? 'text-green-400' : 'text-red-400' }}">
                                            {{ $row['winRate'] }}%
                                        </span>
                                    </td>
                                    <td class="text-right">{{ $row['avgKd'] }}</td>
                                    <td class="text-right text-sm" style="color: var(--color-text-muted)">{{ number_format($row['avgDamage']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        {{-- By mode --}}
        <x-ui.card title="By Mode">
            @if($stats['byMode']->isEmpty())
                <p class="text-sm" style="color: var(--color-text-muted)">No data.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mode</th>
                                <th class="text-right">Matches</th>
                                <th class="text-right">Win %</th>
                                <th class="text-right">K/D</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['byMode'] as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-right text-sm" style="color: var(--color-text-muted)">{{ $row['matches'] }}</td>
                                    <td class="text-right">
                                        <span class="{{ $row['winRate'] >= 50 ? 'text-green-400' : 'text-red-400' }}">
                                            {{ $row['winRate'] }}%
                                        </span>
                                    </td>
                                    <td class="text-right">{{ $row['avgKd'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">

        {{-- By weapon --}}
        <x-ui.card title="By Weapon">
            @if($stats['byWeapon']->isEmpty())
                <p class="text-sm" style="color: var(--color-text-muted)">No weapon data.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Weapon</th>
                                <th>Category</th>
                                <th class="text-right">Used</th>
                                <th class="text-right">Accuracy</th>
                                <th class="text-right">K/D</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['byWeapon'] as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-sm" style="color: var(--color-text-muted)">{{ $row['category'] }}</td>
                                    <td class="text-right text-sm" style="color: var(--color-text-muted)">{{ $row['timesUsed'] }}</td>
                                    <td class="text-right text-sm" style="color: var(--color-text-muted)">
                                        {{ $row['avgAccuracy'] !== null ? $row['avgAccuracy'] . '%' : '—' }}
                                    </td>
                                    <td class="text-right">{{ $row['avgKd'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        {{-- Weekly trend --}}
        <x-ui.card title="Weekly Trend">
            @if($stats['weeklyTrend']->isEmpty())
                <p class="text-sm" style="color: var(--color-text-muted)">No data.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Week of</th>
                                <th class="text-right">Matches</th>
                                <th class="text-right">Win %</th>
                                <th class="text-right">K/D</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['weeklyTrend'] as $row)
                                <tr>
                                    <td class="text-sm" style="color: var(--color-text-muted)">
                                        {{ $row['weekStart']->format('d M Y') }}
                                    </td>
                                    <td class="text-right text-sm" style="color: var(--color-text-muted)">{{ $row['matches'] }}</td>
                                    <td class="text-right">
                                        <span class="{{ $row['winRate'] >= 50 ? 'text-green-400' : 'text-red-400' }}">
                                            {{ $row['winRate'] }}%
                                        </span>
                                    </td>
                                    <td class="text-right">{{ $row['avgKd'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

    </div>

    {{-- Records --}}
    <x-ui.card title="Records">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            @php
                $recordItems = [
                    ['label' => 'Highest Score',  'match' => $stats['records']['highestScore'],  'field' => 'score',  'format' => fn($v) => number_format($v)],
                    ['label' => 'Most Kills',      'match' => $stats['records']['mostKills'],      'field' => 'kills',  'format' => fn($v) => $v],
                    ['label' => 'Most Damage',     'match' => $stats['records']['mostDamage'],     'field' => 'damage', 'format' => fn($v) => number_format($v)],
                    ['label' => 'Best K/D',        'match' => $stats['records']['bestKd'],         'field' => 'kd_ratio', 'format' => fn($v) => $v],
                ];
            @endphp

            @foreach($recordItems as $item)
                <div class="cod-record">
                    <div class="cod-record__label">{{ $item['label'] }}</div>
                    @if($item['match'])
                        <div class="cod-record__value">{{ $item['format']($item['match']->{$item['field']}) }}</div>
                        <div class="cod-record__meta">
                            {{ $item['match']->map->name }} · {{ $item['match']->mode->name }}
                        </div>
                        <div class="cod-record__date">
                            {{ $item['match']->estimated_date->format('d M Y') }}
                        </div>
                    @else
                        <div class="cod-record__value">—</div>
                    @endif
                </div>
            @endforeach

        </div>
    </x-ui.card>

    @endif

</x-layouts.app>
