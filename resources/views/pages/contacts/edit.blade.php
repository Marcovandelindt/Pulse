<x-layouts.app :title="'Edit — ' . $contact->name">

    <x-layout.page-header :title="'Edit: ' . $contact->name">
        <x-slot:actions>
            <a href="{{ route('people.show', $contact) }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('people.update', $contact) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="flex flex-col gap-5">

            <div class="form-group">
                <label class="form-label" for="name">Name <span style="color: #ef4444">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $contact->name) }}"
                    class="form-input"
                    placeholder="e.g. Sarah Johnson"
                    required
                >
                <x-form.error name="name" />
            </div>

            <div class="form-group">
                <label class="form-label" for="birthdate">Birthday</label>
                <input
                    type="date"
                    id="birthdate"
                    name="birthdate"
                    value="{{ old('birthdate', $contact->birthdate?->format('Y-m-d')) }}"
                    class="form-input"
                >
                <x-form.error name="birthdate" />
            </div>

            <div class="form-group">
                <label class="form-label" for="relationship_type_id">Relationship type</label>
                <select id="relationship_type_id" name="relationship_type_id" class="form-input">
                    <option value="">— None —</option>
                    @foreach ($relationshipTypes as $type)
                        <option value="{{ $type->id }}"
                            {{ old('relationship_type_id', $contact->relationship_type_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                <x-form.error name="relationship_type_id" />
            </div>

            <div class="form-group">
                <label class="form-label">Photo</label>
                @if ($contact->photo)
                    <div class="mb-3">
                        <img
                            src="{{ Storage::url($contact->photo) }}"
                            alt="{{ $contact->name }}"
                            style="width: 5rem; height: 5rem; object-fit: cover; border-radius: var(--radius-md); display: block;"
                        >
                    </div>
                @endif
                <input
                    type="file"
                    id="photo"
                    name="photo"
                    accept="image/*"
                    class="form-input"
                >
                @if ($contact->photo)
                    <p class="text-xs mt-1" style="color: var(--color-text-muted)">Upload a new photo to replace the current one.</p>
                @endif
                <x-form.error name="photo" />
            </div>

            <div class="form-group">
                <label class="form-label" for="notes">Notes</label>
                <textarea
                    id="notes"
                    name="notes"
                    class="form-textarea"
                    rows="4"
                    placeholder="Anything worth remembering..."
                >{{ old('notes', $contact->notes) }}</textarea>
                <x-form.error name="notes" />
            </div>

            </div>{{-- /gap wrapper --}}

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Save changes</button>
                <a href="{{ route('people.show', $contact) }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>

</x-layouts.app>
