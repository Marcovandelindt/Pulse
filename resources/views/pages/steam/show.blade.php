<x-layouts.app :title="$game->name">

<x-layout.page-header :title="$game->name">
    <x-slot:actions>
        <a href="{{ $game->steam_url }}" target="_blank" rel="noopener"
           class="btn btn--secondary btn--sm">View on Steam ↗</a>
        <a href="{{ route('steam.games.edit', $game) }}" class="btn btn--secondary btn--sm">Edit</a>
        <a href="{{ route('steam.index') }}" class="btn btn--secondary btn--sm">← Back</a>
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <div class="lg:col-span-2 space-y-6">

        <x-ui.card>
            <div class="gaming-hero">
                <div class="gaming-hero__cover" style="width: 7.5rem;">
                    @if($game->image_url)
                        <img src="{{ $game->image_url }}" alt="{{ $game->name }}"
                             class="gaming-hero__img">
                    @else
                        <div style="width: 7.5rem; height: 7.5rem; background: var(--color-bg-tertiary); border-radius: var(--radius-md);"></div>
                    @endif
                </div>

                <div class="gaming-hero__info">
                    <h2 class="text-xl font-bold mb-1" style="color: var(--color-text-primary)">
                        {{ $game->name }}
                    </h2>

                    <div class="flex items-center gap-2 flex-wrap mb-4">
                        @if($game->backlog_status)
                            <span class="badge">
                                {{ $game->backlog_status->icon() }} {{ $game->backlog_status->label() }}
                            </span>
                        @endif
                        @if($game->play_mode)
                            <span class="badge">
                                {{ $game->play_mode->icon() }} {{ $game->play_mode->label() }}
                            </span>
                        @endif
                        @if($game->main_story_completed)
                            <span class="badge" style="background: rgba(34,197,94,0.15); color: #4ade80;">
                                ✅ Story Completed
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Total</div>
                            <div class="text-lg font-bold" style="color: var(--color-text-primary)">{{ $game->formatted_playtime }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Last 2 Weeks</div>
                            <div class="text-lg font-bold" style="color: var(--color-text-primary)">
                                {{ $game->playtime_2weeks_minutes !== null
                                    ? $game->playtime2weeks_hours . 'h'
                                    : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Last Played</div>
                            <div class="text-sm font-medium" style="color: var(--color-text-primary)">
                                {{ $game->last_played_at?->format('d M Y') ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Price</div>
                            <div class="text-lg font-bold" style="color: var(--color-text-primary)">
                                {{ $game->price ? '€ ' . number_format((float) $game->price, 2, ',', '.') : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Statistics">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div>
                    <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Hours Played</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ number_format($game->playtime_hours, 1) }}h</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Minutes Played</div>
                    <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ number_format($game->playtime_minutes) }}</div>
                </div>
                @if($costPerHour !== null)
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Cost / Hour</div>
                        <div class="text-2xl font-bold" style="color: var(--color-text-primary)">€{{ number_format($costPerHour, 2) }}</div>
                        <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ $valueRating }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Cost / Minute</div>
                        <div class="text-lg font-bold" style="color: var(--color-text-primary)">€{{ number_format($costPerMinute, 4) }}</div>
                    </div>
                @endif
                @if($game->user_rating)
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Your Rating</div>
                        <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ $game->user_rating }}<span class="text-sm font-normal" style="color: var(--color-text-muted)">/10</span></div>
                    </div>
                @endif
                @if($game->critic_rating)
                    <div>
                        <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-muted)">Critic Rating</div>
                        <div class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ $game->critic_rating }}<span class="text-sm font-normal" style="color: var(--color-text-muted)">/100</span></div>
                    </div>
                @endif
            </div>
        </x-ui.card>

    </div>

    <div class="space-y-6">

        <x-ui.card title="Backlog Status">
            <form method="POST" action="{{ route('gaming.backlog.update', ['type' => 'steam', 'id' => $game->id]) }}">
                @csrf
                @method('PATCH')
                <select name="backlog_status" class="form-input w-full mb-3"
                        onchange="this.form.submit()">
                    <option value="">— Untracked</option>
                    @foreach(\App\Enums\BacklogStatus::cases() as $bs)
                        <option value="{{ $bs->value }}" {{ $game->backlog_status === $bs ? 'selected' : '' }}>
                            {{ $bs->icon() }} {{ $bs->label() }}
                        </option>
                    @endforeach
                </select>
            </form>
        </x-ui.card>

        <x-ui.card title="Genres">
            @if($game->genres->isEmpty())
                <p class="text-sm" style="color: var(--color-text-muted)">No genres set. <a href="{{ route('steam.games.edit', $game) }}" style="color: var(--color-brand)">Edit</a> to add.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($game->genres as $genre)
                        <span class="badge">{{ $genre->name }}</span>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Metadata">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span style="color: var(--color-text-muted)">App ID</span>
                    <span style="color: var(--color-text-primary)">{{ $game->steam_appid }}</span>
                </div>
                <div class="flex justify-between">
                    <span style="color: var(--color-text-muted)">Steam URL</span>
                    <a href="{{ $game->steam_url }}" target="_blank" rel="noopener"
                       style="color: var(--color-brand)">Open ↗</a>
                </div>
                <div class="flex justify-between">
                    <span style="color: var(--color-text-muted)">Added</span>
                    <span style="color: var(--color-text-primary)">{{ $game->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </x-ui.card>

    </div>

</div>

</x-layouts.app>
