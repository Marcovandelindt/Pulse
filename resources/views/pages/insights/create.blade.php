<x-layouts.app title="New Insight">

    <x-layout.page-header title="New insight">
        <x-slot:actions>
            <a href="{{ route('insights.index') }}" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="insight-form-wrap">
        <form method="POST" action="{{ route('insights.store') }}" class="insight-form">
            @csrf

            <div class="form-group">
                <label class="form-label" for="title">Title <span style="color: #ef4444">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                    class="form-input"
                    placeholder="Name this insight..."
                    required
                    autofocus
                >
                <x-form.error name="title" />
            </div>

            <div class="form-group">
                <label class="form-label">Insight <span style="color: #ef4444">*</span></label>
                <div
                    data-quill
                    data-target="#content-input"
                    data-content="{{ old('content') }}"
                    data-placeholder="Describe the insight in your own words..."
                    class="quill-editor"
                ></div>
                <input type="hidden" name="content" id="content-input">
                <x-form.error name="content" />
            </div>

            {{-- Optional fields --}}
            <div x-data="{ open: {{ old('summary') || old('category') || old('tags') ? 'true' : 'false' }} }">
                <button
                    type="button"
                    @click="open = !open"
                    class="insight-form__toggle"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="insight-form__toggle-icon" :class="{ 'insight-form__toggle-icon--open': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    More options
                </button>

                <div x-show="open" x-transition class="insight-form__extra">
                    <div class="form-group">
                        <label class="form-label" for="summary">Summary <span class="form-label__optional">optional</span></label>
                        <input
                            type="text"
                            id="summary"
                            name="summary"
                            value="{{ old('summary') }}"
                            class="form-input"
                            placeholder="One-sentence summary..."
                            maxlength="500"
                        >
                        <x-form.error name="summary" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category">Category <span class="form-label__optional">optional</span></label>
                        <input
                            type="text"
                            id="category"
                            name="category"
                            value="{{ old('category') }}"
                            class="form-input"
                            placeholder="e.g. Work, Emotions, Behaviour"
                            maxlength="100"
                        >
                        <x-form.error name="category" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tags">Tags <span class="form-label__optional">optional — comma-separated</span></label>
                        <input
                            type="text"
                            id="tags"
                            name="tags"
                            value="{{ old('tags') }}"
                            class="form-input"
                            placeholder="e.g. Certainty, Control, Work"
                        >
                        <x-form.error name="tags" />
                    </div>

                    <div class="flex gap-4">
                        <label class="flex items-center gap-2" style="cursor: pointer;">
                            <input type="checkbox" name="is_pinned" value="1"
                                   class="form-checkbox" {{ old('is_pinned') ? 'checked' : '' }}>
                            <span style="font-size: 0.875rem; color: var(--color-text-primary);">Pin this insight</span>
                        </label>
                        <label class="flex items-center gap-2" style="cursor: pointer;">
                            <input type="checkbox" name="is_quick_ref" value="1"
                                   class="form-checkbox" {{ old('is_quick_ref') ? 'checked' : '' }}>
                            <span style="font-size: 0.875rem; color: var(--color-text-primary);">Quick reference</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save insight</button>
                <a href="{{ route('insights.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

    <x-layout.notification />

</x-layouts.app>
