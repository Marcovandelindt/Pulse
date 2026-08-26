<x-layouts.app title="Edit Insight">

    <x-layout.page-header title="Edit insight">
        <x-slot:actions>
            <a href="{{ route('insights.show', $insight) }}" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="insight-form-wrap">
        <form method="POST" action="{{ route('insights.update', $insight) }}" class="insight-form">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label class="form-label" for="title">Title <span style="color: #ef4444">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $insight->title) }}"
                    class="form-input"
                    required
                    autofocus
                >
                <x-form.error name="title" />
            </div>

            <div class="form-group">
                <label class="form-label" for="content">Insight <span style="color: #ef4444">*</span></label>
                <textarea
                    id="content"
                    name="content"
                    class="form-textarea"
                    rows="6"
                    required
                >{{ old('content', $insight->content) }}</textarea>
                <x-form.error name="content" />
            </div>

            <div class="form-group">
                <label class="form-label" for="summary">Summary <span class="form-label__optional">optional</span></label>
                <input
                    type="text"
                    id="summary"
                    name="summary"
                    value="{{ old('summary', $insight->summary) }}"
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
                    value="{{ old('category', $insight->category) }}"
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
                    value="{{ old('tags', implode(', ', $insight->tags ?? [])) }}"
                    class="form-input"
                    placeholder="e.g. Certainty, Control, Work"
                >
                <x-form.error name="tags" />
            </div>

            <div class="flex gap-4">
                <label class="flex items-center gap-2" style="cursor: pointer;">
                    <input type="checkbox" name="is_pinned" value="1"
                           class="form-checkbox" {{ old('is_pinned', $insight->is_pinned) ? 'checked' : '' }}>
                    <span style="font-size: 0.875rem; color: var(--color-text-primary);">Pin this insight</span>
                </label>
                <label class="flex items-center gap-2" style="cursor: pointer;">
                    <input type="checkbox" name="is_quick_ref" value="1"
                           class="form-checkbox" {{ old('is_quick_ref', $insight->is_quick_ref) ? 'checked' : '' }}>
                    <span style="font-size: 0.875rem; color: var(--color-text-primary);">Quick reference</span>
                </label>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save changes</button>
                <a href="{{ route('insights.show', $insight) }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

    <x-layout.notification />

</x-layouts.app>
