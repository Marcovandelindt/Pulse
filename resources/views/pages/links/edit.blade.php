<x-layouts.app title="Edit Link">

    <x-layout.page-header title="Edit Link">
        <x-slot:actions>
            <a href="{{ route('links.show', $link) }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('links.update', $link) }}">
            @csrf
            @method('PATCH')

            <div class="flex flex-col gap-5">

                <div class="form-group">
                    <label class="form-label" for="url">URL <span style="color: #ef4444">*</span></label>
                    <input
                        type="url"
                        id="url"
                        name="url"
                        value="{{ old('url', $link->url) }}"
                        class="form-input"
                        placeholder="https://example.com"
                        required
                    >
                    <x-form.error name="url" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="title">Title <span style="color: #ef4444">*</span></label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="{{ old('title', $link->title) }}"
                        class="form-input"
                        placeholder="e.g. An interesting article"
                        required
                    >
                    <x-form.error name="title" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        class="form-textarea"
                        rows="4"
                        placeholder="Why is this link interesting?"
                    >{{ old('description', $link->description) }}</textarea>
                    <x-form.error name="description" />
                </div>

            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save changes</button>
                <a href="{{ route('links.show', $link) }}" class="btn btn--secondary">Cancel</a>
            </div>

        </form>
    </x-ui.card>

</x-layouts.app>
