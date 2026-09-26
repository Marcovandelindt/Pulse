<x-layouts.app :title="$game->name">
    <div class="page-header">
        <div class="page-header__left">
            <a href="{{ route('nintendo.index') }}" class="page-header__back">Nintendo Switch</a>
            <h1 class="page-header__title">{{ $game->name }}</h1>
        </div>
        <div class="page-header__actions">
            <form method="POST" action="{{ route('nintendo.destroy', $game) }}"
                  onsubmit="return confirm('Remove {{ addslashes($game->name) }} and all its records?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Remove</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert--success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Hero --}}
    <div class="gaming-hero mb-6">
        @if($game->image_url)
            <div class="gaming-hero__cover">
                <img src="{{ $game->image_url }}" alt="{{ $game->name }}" class="gaming-hero__img">
                <form method="POST" action="{{ route('nintendo.cover.store', $game) }}"
                      enctype="multipart/form-data" class="gaming-hero__cover-upload">
                    @csrf
                    <label class="gaming-hero__cover-label">
                        <input type="file" name="cover" accept="image/*" class="sr-only"
                               onchange="this.form.submit()">
                        Upload cover
                    </label>
                </form>
            </div>
        @else
            <div class="gaming-hero__cover gaming-hero__cover--empty">
                <form method="POST" action="{{ route('nintendo.cover.store', $game) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <label class="gaming-hero__cover-label gaming-hero__cover-label--empty">
                        <input type="file" name="cover" accept="image/*" class="sr-only"
                               onchange="this.form.submit()">
                        <span>🖼️</span>
                        <span>Upload cover</span>
                    </label>
                </form>
            </div>
        @endif

        <div class="gaming-hero__info">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-4">
                <div>
                    <div class="stat-card__label">Total playtime</div>
                    <div style="font-size:1.75rem;font-weight:700;color:var(--color-text-primary);line-height:1.1;">
                        {{ $game->formatted_hours ?: '—' }}
                    </div>
                </div>
                <div>
                    <div class="stat-card__label">Sessions logged</div>
                    <div style="font-size:1.75rem;font-weight:700;color:var(--color-text-primary);line-height:1.1;">
                        {{ $totalRecords }}
                    </div>
                </div>
                @if($game->first_played_at)
                    <div>
                        <div class="stat-card__label">First played</div>
                        <div style="font-size:1rem;font-weight:600;color:var(--color-text-primary);">
                            {{ $game->first_played_at->format('M j, Y') }}
                        </div>
                    </div>
                @endif
                @if($game->last_played_at)
                    <div>
                        <div class="stat-card__label">Last played</div>
                        <div style="font-size:1rem;font-weight:600;color:var(--color-text-primary);">
                            {{ $game->last_played_at->diffForHumans() }}
                        </div>
                    </div>
                @endif
            </div>

            @if($game->genres)
                <div style="display:flex;flex-wrap:wrap;gap:.375rem;">
                    @foreach($game->genres as $genre)
                        <span class="badge badge--subtle">{{ $genre }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 items-start">

        {{-- Log session --}}
        <x-ui.card title="Log session">
            <form method="POST" action="{{ route('nintendo.records.store', $game) }}" x-data="sessionForm()">
                @csrf

                <div style="margin-bottom:1rem;">
                    <label class="form-label" for="date">Date</label>
                    <input
                        id="date"
                        type="date"
                        name="date"
                        class="form-input @error('date') form-input--error @enderror"
                        value="{{ old('date', today()->format('Y-m-d')) }}"
                        max="{{ today()->format('Y-m-d') }}"
                        required
                    >
                    @error('date')
                        <x-form.error>{{ $message }}</x-form.error>
                    @enderror
                </div>

                <div style="margin-bottom:1rem;">
                    <label class="form-label">Duration</label>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <input
                            type="number"
                            placeholder="0"
                            min="0"
                            max="23"
                            x-model.number="hours"
                            @input="updateMinutes"
                            class="form-input"
                            style="width:5rem;"
                        >
                        <span style="color:var(--color-text-muted);font-size:.875rem;">h</span>
                        <input
                            type="number"
                            placeholder="0"
                            min="0"
                            max="59"
                            x-model.number="mins"
                            @input="updateMinutes"
                            class="form-input"
                            style="width:5rem;"
                        >
                        <span style="color:var(--color-text-muted);font-size:.875rem;">m</span>
                    </div>
                    <input type="hidden" name="minutes_played" x-model="total">
                    @error('minutes_played')
                        <x-form.error>{{ $message }}</x-form.error>
                    @enderror
                </div>

                <button type="submit" class="btn btn--primary" style="width:100%;" :disabled="total < 1">
                    Log session
                </button>
            </form>
        </x-ui.card>

        {{-- Play history --}}
        <div class="lg:col-span-2">
            <x-ui.card title="Play history">
                @if($records->isEmpty())
                    <x-ui.empty-state
                        title="No sessions yet"
                        description="Log your first session on the left."
                        icon="clock"
                    />
                @else
                    <div class="gaming-session-list">
                        @foreach($records as $record)
                            @php
                                $h = intdiv($record->minutes_played, 60);
                                $m = $record->minutes_played % 60;
                                $duration = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                            @endphp
                            <div class="gaming-session-item">
                                <div>
                                    <div style="font-size:.875rem;font-weight:500;color:var(--color-text-primary);">
                                        {{ $record->date->format('D, M j Y') }}
                                    </div>
                                </div>
                                <div class="gaming-session-item__meta">
                                    <span class="gaming-session-item__duration">{{ $duration }}</span>
                                    <form method="POST" action="{{ route('nintendo.records.destroy', [$game, $record]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:var(--color-text-muted);cursor:pointer;font-size:.75rem;padding:.125rem .375rem;border-radius:var(--radius-sm);transition:color var(--transition-base);" onmouseenter="this.style.color='#ef4444'" onmouseleave="this.style.color='var(--color-text-muted)'">×</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($records->hasPages())
                        <div class="mt-4">
                            {{ $records->links() }}
                        </div>
                    @endif
                @endif
            </x-ui.card>
        </div>
    </div>

    <script>
        function sessionForm() {
            return {
                hours: 0,
                mins:  30,
                total: 30,
                updateMinutes() {
                    this.total = (this.hours * 60) + this.mins;
                },
            };
        }
    </script>
</x-layouts.app>
