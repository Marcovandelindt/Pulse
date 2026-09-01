<x-layouts.app title="Add Game — PlayStation">

    <x-layout.page-header title="Add Game">
        <x-slot:actions>
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mb-4 px-4 py-3 rounded-lg text-sm"
         style="background: rgba(99,102,241,0.1); color: var(--color-text-primary); border: 1px solid rgba(99,102,241,0.25);">
        <strong style="color: #818cf8;">Manually added game</strong> —
        Use this for games that ps-timetracker doesn't distinguish (e.g. remasters).
        <em>Excluded from sync</em> is on by default so the scraper never overwrites this entry.
        Set <em>Sessions from date</em> to the remaster's release date to exclude pre-remaster sessions from hour totals.
    </div>

    <x-ui.card>
        <form method="POST" action="{{ route('playstation.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Game Name <span style="color: #ef4444">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-input"
                    placeholder="e.g. Days Gone Remastered"
                    required
                >
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                    Must match the name used by ps-timetracker so sessions link up correctly.
                </p>
                <x-form.error name="name" />
            </div>

            <div class="form-group">
                <label class="form-label" for="display_name">Display Name</label>
                <input
                    type="text"
                    id="display_name"
                    name="display_name"
                    value="{{ old('display_name') }}"
                    class="form-input"
                    placeholder="Override shown in the UI"
                >
                <x-form.error name="display_name" />
            </div>

            <div class="form-group">
                <label class="form-label" for="trophy_search_name">Trophy Search Name</label>
                <input
                    type="text"
                    id="trophy_search_name"
                    name="trophy_search_name"
                    value="{{ old('trophy_search_name') }}"
                    class="form-input"
                    placeholder="e.g. Days Gone Remastered"
                >
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                    Override the name used to find this game in your PSN trophy list.
                    Leave empty to use the Game Name above.
                </p>
                <x-form.error name="trophy_search_name" />
            </div>

            <div class="form-group">
                <label class="form-label" for="platform">Platform <span style="color: #ef4444">*</span></label>
                <select id="platform" name="platform" class="form-input" required>
                    <option value="">— Select Platform —</option>
                    @foreach(['PS5', 'PS4', 'PS3', 'PSVITA'] as $p)
                        <option value="{{ $p }}" {{ old('platform') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
                <x-form.error name="platform" />
            </div>

            <div class="form-group">
                <label class="form-label" for="released_at">Sessions from date</label>
                <input
                    type="date"
                    id="released_at"
                    name="released_at"
                    value="{{ old('released_at') }}"
                    class="form-input"
                >
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                    Sessions before this date are excluded from all hour totals.
                    For a remaster: set this to the remaster's release date so original-game sessions don't count.
                </p>
                <x-form.error name="released_at" />
            </div>

            <div class="form-group">
                <label class="form-label" for="np_communication_id">PSN Communication ID</label>
                <input
                    type="text"
                    id="np_communication_id"
                    name="np_communication_id"
                    value="{{ old('np_communication_id') }}"
                    class="form-input"
                    placeholder="e.g. NPWR12345_00"
                >
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                    Optional. If set, trophy fetching skips title-matching and uses this ID directly —
                    useful when the game name is ambiguous (e.g. remaster vs original).
                </p>
                <x-form.error name="np_communication_id" />
            </div>

            <div class="form-group">
                <label class="form-label">Cover Image</label>
                <input type="file" id="image" name="image" accept="image/*" class="form-input">
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">Max 10MB.</p>
                <x-form.error name="image" />
            </div>

            <div class="form-group">
                <label class="form-label" for="price">Price (€)</label>
                <input
                    type="number"
                    id="price"
                    name="price"
                    step="0.01"
                    min="0"
                    value="{{ old('price') }}"
                    class="form-input"
                    placeholder="e.g. 59.99"
                >
                <x-form.error name="price" />
            </div>

            <div class="form-group">
                <label class="form-label">PlayStation Total Playtime</label>
                <div class="flex gap-3">
                    <div class="flex items-center gap-2">
                        <input
                            type="number"
                            id="psn_hours"
                            name="psn_hours"
                            min="0"
                            value="{{ old('psn_hours') }}"
                            class="form-input"
                            style="width: 6rem;"
                            placeholder="0"
                        >
                        <span class="text-sm" style="color: var(--color-text-muted)">hours</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            type="number"
                            id="psn_minutes"
                            name="psn_minutes"
                            min="0"
                            max="59"
                            value="{{ old('psn_minutes') }}"
                            class="form-input"
                            style="width: 6rem;"
                            placeholder="0"
                        >
                        <span class="text-sm" style="color: var(--color-text-muted)">min</span>
                    </div>
                </div>
                <x-form.error name="psn_hours" />
                <x-form.error name="psn_minutes" />
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
                    value="{{ old('user_rating') }}"
                    class="form-input"
                    placeholder="e.g. 8.5"
                >
                <x-form.error name="user_rating" />
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
                    value="{{ old('critic_rating') }}"
                    class="form-input"
                    placeholder="e.g. 9.2"
                >
                <x-form.error name="critic_rating" />
            </div>

            <div class="form-group">
                <label class="form-label" for="backlog_status">Backlog Status</label>
                <select id="backlog_status" name="backlog_status" class="form-input">
                    <option value="">— None —</option>
                    @foreach(\App\Enums\BacklogStatus::cases() as $status)
                        <option value="{{ $status->value }}"
                            {{ old('backlog_status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <x-form.error name="backlog_status" />
            </div>

            <div class="form-group">
                <label class="form-label">Play Mode</label>
                <div class="flex flex-wrap gap-2 mt-1">
                    @foreach(\App\Enums\PlayMode::cases() as $mode)
                        <label class="form-chip">
                            <input
                                type="checkbox"
                                name="play_mode[]"
                                value="{{ $mode->value }}"
                                {{ in_array($mode->value, old('play_mode', [])) ? 'checked' : '' }}
                            >
                            {{ $mode->label() }}
                        </label>
                    @endforeach
                </div>
                <x-form.error name="play_mode" />
            </div>

            <div class="form-group">
                <label class="form-label">Main Story Completed</label>
                <div class="flex gap-2 mt-1">
                    <label class="form-chip">
                        <input
                            type="checkbox"
                            name="main_story_completed"
                            value="1"
                            {{ old('main_story_completed') ? 'checked' : '' }}
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
                            {{ old('exclude_from_sync', true) ? 'checked' : '' }}
                        >
                        Exclude from sync
                    </label>
                </div>
                <p class="text-xs mt-1" style="color: var(--color-text-muted)">
                    Keep this on for manually added games — disabling it lets the scraper update or remove this entry.
                </p>
            </div>

            @if($categories->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">Categories</label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($categories as $category)
                            <label class="form-chip">
                                <input
                                    type="checkbox"
                                    name="categories[]"
                                    value="{{ $category->id }}"
                                    {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}
                                >
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Add Game</button>
                <a href="{{ route('playstation.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>

</x-layouts.app>
