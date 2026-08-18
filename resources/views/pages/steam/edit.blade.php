<x-layouts.app :title="'Edit — ' . $game->name">

<x-layout.page-header :title="'Edit: ' . $game->name">
    <x-slot:actions>
        <a href="{{ route('steam.games.show', $game) }}" class="btn btn--secondary btn--sm">← Cancel</a>
    </x-slot:actions>
</x-layout.page-header>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <div class="lg:col-span-1">
        <x-ui.card>
            <div class="flex flex-col items-center text-center gap-3">
                @if($game->image_url)
                    <img src="{{ $game->image_url }}" alt="{{ $game->name }}"
                         style="width: 7rem; border-radius: var(--radius-md);">
                @else
                    <div style="width: 7rem; height: 7rem; background: var(--color-bg-tertiary); border-radius: var(--radius-md);"></div>
                @endif
                <div>
                    <div class="font-semibold text-sm" style="color: var(--color-text-primary)">{{ $game->name }}</div>
                    <div class="text-xs mt-1" style="color: var(--color-text-muted)">{{ $game->formatted_playtime }} played</div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="lg:col-span-2">
        <x-ui.card>
            <form method="POST" action="{{ route('steam.games.update', $game) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label" for="price">Price (€)</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        step="0.01"
                        min="0"
                        value="{{ old('price', $game->price) }}"
                        class="form-input"
                        placeholder="e.g. 29.99"
                    >
                    <x-form.error name="price" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="user_rating">Your Rating (1–10)</label>
                    <input
                        type="number"
                        id="user_rating"
                        name="user_rating"
                        min="1"
                        max="10"
                        value="{{ old('user_rating', $game->user_rating) }}"
                        class="form-input"
                        placeholder="1–10"
                    >
                    <x-form.error name="user_rating" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="critic_rating">Critic Rating (1–100)</label>
                    <input
                        type="number"
                        id="critic_rating"
                        name="critic_rating"
                        min="1"
                        max="100"
                        value="{{ old('critic_rating', $game->critic_rating) }}"
                        class="form-input"
                        placeholder="Metacritic score, e.g. 87"
                    >
                    <x-form.error name="critic_rating" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="play_mode">Play Mode</label>
                    <select id="play_mode" name="play_mode" class="form-input">
                        <option value="">— None</option>
                        @foreach($playModes as $mode)
                            <option value="{{ $mode->value }}" {{ old('play_mode', $game->play_mode?->value) === $mode->value ? 'selected' : '' }}>
                                {{ $mode->icon() }} {{ $mode->label() }}
                            </option>
                        @endforeach
                    </select>
                    <x-form.error name="play_mode" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="backlog_status">Backlog Status</label>
                    <select id="backlog_status" name="backlog_status" class="form-input">
                        <option value="">— Untracked</option>
                        @foreach($backlogStatuses as $bs)
                            <option value="{{ $bs->value }}" {{ old('backlog_status', $game->backlog_status?->value) === $bs->value ? 'selected' : '' }}>
                                {{ $bs->icon() }} {{ $bs->label() }}
                            </option>
                        @endforeach
                    </select>
                    <x-form.error name="backlog_status" />
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <input
                            type="hidden"
                            name="main_story_completed"
                            value="0"
                        >
                        <input
                            type="checkbox"
                            name="main_story_completed"
                            value="1"
                            {{ old('main_story_completed', $game->main_story_completed) ? 'checked' : '' }}
                            style="margin-right: 0.4rem;"
                        >
                        Main Story Completed
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Genres</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($genres as $genre)
                            <label class="flex items-center gap-1 text-sm cursor-pointer px-2 py-1 rounded"
                                   style="border: 1px solid var(--color-border); background: var(--color-bg-tertiary);">
                                <input
                                    type="checkbox"
                                    name="genres[]"
                                    value="{{ $genre->id }}"
                                    {{ $game->genres->contains($genre) ? 'checked' : '' }}
                                >
                                {{ $genre->name }}
                            </label>
                        @endforeach
                    </div>
                    @if($genres->isEmpty())
                        <p class="text-sm mt-1" style="color: var(--color-text-muted)">No genres in database yet.</p>
                    @endif
                    <x-form.error name="genres" />
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn--primary">Save</button>
                    <a href="{{ route('steam.games.show', $game) }}" class="btn btn--secondary">Cancel</a>
                </div>

            </form>
        </x-ui.card>
    </div>

</div>

</x-layouts.app>
