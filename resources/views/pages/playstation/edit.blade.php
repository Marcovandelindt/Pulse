<x-layouts.app :title="'Edit — ' . $game->name">

    <x-layout.page-header :title="'Edit: ' . $game->name">
        <x-slot:actions>
            <a href="{{ route('playstation.show', $game) }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('playstation.update', $game) }}" enctype="multipart/form-data" class="space-y-6">
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
                <label class="form-label">Play Mode</label>
                @php
                    $selectedModes = old('play_mode', $game->play_mode?->map(fn($m) => $m->value)->toArray() ?? []);
                @endphp
                <div class="flex flex-wrap gap-2 mt-1">
                    @foreach(\App\Enums\PlayMode::cases() as $mode)
                        <label class="form-chip">
                            <input
                                type="checkbox"
                                name="play_mode[]"
                                value="{{ $mode->value }}"
                                {{ in_array($mode->value, $selectedModes) ? 'checked' : '' }}
                            >
                            {{ $mode->label() }}
                        </label>
                    @endforeach
                </div>
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
                <label class="form-label">Main Story Completed</label>
                <div class="flex gap-2 mt-1">
                    <label class="form-chip">
                        <input
                            type="checkbox"
                            name="main_story_completed"
                            value="1"
                            {{ old('main_story_completed', $game->main_story_completed) ? 'checked' : '' }}
                        >
                        Yes, completed
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Exclude from Sync</label>
                <div class="flex gap-2 mt-1">
                    <label class="form-chip">
                        <input
                            type="checkbox"
                            name="exclude_from_sync"
                            value="1"
                            {{ old('exclude_from_sync', $game->exclude_from_sync) ? 'checked' : '' }}
                        >
                        Exclude from sync
                    </label>
                </div>
            </div>

            @if($categories->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">Categories</label>
                    @php
                        $selectedCategories = old('categories', $game->categories->pluck('id')->toArray());
                    @endphp
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($categories as $category)
                            <label class="form-chip">
                                <input
                                    type="checkbox"
                                    name="categories[]"
                                    value="{{ $category->id }}"
                                    {{ in_array($category->id, $selectedCategories) ? 'checked' : '' }}
                                >
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                        <a href="{{ route('playstation.categories.index') }}" style="color: var(--color-brand)">Manage categories</a>
                    </p>
                </div>
            @else
                <div class="form-group">
                    <label class="form-label">Categories</label>
                    <p class="text-sm" style="color: var(--color-text-muted)">
                        No categories yet. <a href="{{ route('playstation.categories.index') }}" style="color: var(--color-brand)">Create some first.</a>
                    </p>
                </div>
            @endif

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save Changes</button>
                <a href="{{ route('playstation.show', $game) }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>

</x-layouts.app>
