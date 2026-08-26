<x-layouts.app title="Edit Pattern">

    <x-layout.page-header title="Edit pattern">
        <x-slot:actions>
            <a href="{{ route('patterns.show', $pattern) }}" class="btn btn--secondary btn--sm">&larr; Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="insight-form-wrap">
        <form method="POST" action="{{ route('patterns.update', $pattern) }}" class="insight-form">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label class="form-label" for="title">Title <span style="color: #ef4444">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $pattern->title) }}"
                    class="form-input"
                    required
                    autofocus
                >
                <x-form.error name="title" />
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="form-label__optional">optional</span></label>
                <textarea
                    id="description"
                    name="description"
                    class="form-textarea"
                    rows="4"
                    placeholder="Describe the recurring pattern..."
                >{{ old('description', $pattern->description) }}</textarea>
                <x-form.error name="description" />
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save changes</button>
                <a href="{{ route('patterns.show', $pattern) }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </div>

    <x-layout.notification />

</x-layouts.app>
