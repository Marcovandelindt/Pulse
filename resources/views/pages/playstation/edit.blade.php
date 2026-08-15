<x-layouts.app :title="'Edit — ' . $game->name">

    <x-layout.page-header :title="'Edit: ' . $game->name">
        <x-slot:actions>
            <a href="{{ route('playstation.show', $game) }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('playstation.update', $game) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label class="form-label">Cover Image</label>
                @if($game->image_url)
                    <div class="mb-3">
                        <img src="{{ $game->image_url }}" alt="{{ $game->name }}"
                             style="width: 6rem; border-radius: var(--radius-md); display: block;">
                    </div>
                @endif
                <input type="file" id="image" name="image" accept="image/*" class="form-input">
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">Max 2MB. Uploads a new cover and replaces the existing one.</p>
                @error('image')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

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
                    placeholder="e.g. 59.99"
                >
                @error('price')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="manual_minutes">Manual Minutes</label>
                <input
                    type="number"
                    id="manual_minutes"
                    name="manual_minutes"
                    min="0"
                    value="{{ old('manual_minutes', $game->manual_minutes) }}"
                    class="form-input"
                    placeholder="Extra playtime in minutes"
                >
                @error('manual_minutes')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="user_rating">Your Rating (0–10)</label>
                <input
                    type="number"
                    id="user_rating"
                    name="user_rating"
                    step="0.1"
                    min="0"
                    max="10"
                    value="{{ old('user_rating', $game->user_rating) }}"
                    class="form-input"
                    placeholder="e.g. 8.5"
                >
                @error('user_rating')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="critic_rating">Critic Rating (0–10)</label>
                <input
                    type="number"
                    id="critic_rating"
                    name="critic_rating"
                    step="0.1"
                    min="0"
                    max="10"
                    value="{{ old('critic_rating', $game->critic_rating) }}"
                    class="form-input"
                    placeholder="e.g. 9.2"
                >
                @error('critic_rating')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="backlog_status">Backlog Status</label>
                <select id="backlog_status" name="backlog_status" class="form-input">
                    <option value="">— None —</option>
                    @foreach(\App\Enums\BacklogStatus::cases() as $status)
                        <option value="{{ $status->value }}"
                            {{ old('backlog_status', $game->backlog_status?->value) === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('backlog_status')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="play_mode">Play Mode</label>
                <select id="play_mode" name="play_mode" class="form-input">
                    <option value="">— None —</option>
                    @foreach(\App\Enums\PlayMode::cases() as $mode)
                        <option value="{{ $mode->value }}"
                            {{ old('play_mode', $game->play_mode?->value) === $mode->value ? 'selected' : '' }}>
                            {{ $mode->label() }}
                        </option>
                    @endforeach
                </select>
                @error('play_mode')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="completion_percentage">Completion %</label>
                <input
                    type="number"
                    id="completion_percentage"
                    name="completion_percentage"
                    step="0.01"
                    min="0"
                    max="100"
                    value="{{ old('completion_percentage', $game->completion_percentage) }}"
                    class="form-input"
                >
                @error('completion_percentage')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="trophies">Trophies</label>
                <input
                    type="number"
                    id="trophies"
                    name="trophies"
                    min="0"
                    value="{{ old('trophies', $game->trophies) }}"
                    class="form-input"
                >
                @error('trophies')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="main_story_completed"
                        value="1"
                        {{ old('main_story_completed', $game->main_story_completed) ? 'checked' : '' }}
                        class="rounded"
                        style="accent-color: var(--color-brand)"
                    >
                    <span class="form-label" style="margin-bottom: 0">Main Story Completed</span>
                </label>
            </div>

            <div class="form-group">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="exclude_from_sync"
                        value="1"
                        {{ old('exclude_from_sync', $game->exclude_from_sync) ? 'checked' : '' }}
                        class="rounded"
                        style="accent-color: var(--color-brand)"
                    >
                    <span class="form-label" style="margin-bottom: 0">Exclude from Sync</span>
                </label>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save Changes</button>
                <a href="{{ route('playstation.show', $game) }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>

</x-layouts.app>
