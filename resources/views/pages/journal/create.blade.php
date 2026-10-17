<x-layouts.app title="New Journal Entry">

<x-layout.page-header title="New Entry">
    <x-slot:actions>
        <a href="{{ route('journal.index') }}" class="btn btn--secondary btn--sm">← Journal</a>
    </x-slot:actions>
</x-layout.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('journal.store') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label" for="date">Date</label>
                <input type="date" id="date" name="date"
                       value="{{ old('date', $suggestedDate) }}"
                       max="{{ today()->format('Y-m-d') }}"
                       class="form-input" required>
                <x-form.error name="date" />
            </div>

            <div class="form-group">
                <label class="form-label" for="title">Title <span style="color:var(--color-text-muted);font-weight:400">(optional)</span></label>
                <input type="text" id="title" name="title"
                       value="{{ old('title') }}"
                       placeholder="Leave blank to use the date"
                       class="form-input" maxlength="255">
                <x-form.error name="title" />
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="content">Entry</label>
            <textarea id="content" name="content" rows="12"
                      class="form-input" style="resize:vertical;"
                      placeholder="What's on your mind today?">{{ old('content') }}</textarea>
            <x-form.error name="content" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label">Mood</label>
                <div class="journal-mood-picker">
                    @foreach(\App\Enums\Mood::cases() as $mood)
                        <label class="journal-mood-picker__option">
                            <input type="radio" name="mood" value="{{ $mood->value }}"
                                   {{ old('mood') === $mood->value ? 'checked' : '' }}>
                            <span class="journal-mood-picker__emoji" title="{{ $mood->label() }}">{{ $mood->emoji() }}</span>
                        </label>
                    @endforeach
                </div>
                <x-form.error name="mood" />
            </div>

            <div class="form-group">
                <label class="form-label">Energy level</label>
                <div class="journal-energy-picker">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="journal-energy-picker__option">
                            <input type="radio" name="energy_level" value="{{ $i }}"
                                   {{ old('energy_level') == $i ? 'checked' : '' }}>
                            <span class="journal-energy-picker__dot">{{ $i }}</span>
                        </label>
                    @endfor
                </div>
                <x-form.error name="energy_level" />
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="tags">Tags <span style="color:var(--color-text-muted);font-weight:400">(optional, comma-separated)</span></label>
            <input type="text" id="tags" name="tags"
                   value="{{ old('tags') }}"
                   placeholder="e.g. work, reflection, gratitude"
                   class="form-input" maxlength="500">
            <x-form.error name="tags" />
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn--primary">Save entry</button>
            <a href="{{ route('journal.index') }}" class="btn btn--secondary">Cancel</a>
        </div>
    </form>
</x-ui.card>

</x-layouts.app>
