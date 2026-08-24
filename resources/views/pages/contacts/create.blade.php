<x-layouts.app title="Add Person">

    <x-layout.page-header title="Add Person">
        <x-slot:actions>
            <a href="{{ route('people.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('people.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Name <span style="color: #ef4444">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-input"
                    placeholder="e.g. Sarah Johnson"
                    required
                    autofocus
                >
                <x-form.error name="name" />
            </div>

            <div class="form-group">
                <label class="form-label" for="birthdate">Birthday</label>
                <input
                    type="date"
                    id="birthdate"
                    name="birthdate"
                    value="{{ old('birthdate') }}"
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
                            {{ old('relationship_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                <x-form.error name="relationship_type_id" />
            </div>

            <div class="form-group">
                <label class="form-label" for="photo">Photo</label>
                <input
                    type="file"
                    id="photo"
                    name="photo"
                    accept="image/*"
                    class="form-input"
                >
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
                >{{ old('notes') }}</textarea>
                <x-form.error name="notes" />
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Add person</button>
                <a href="{{ route('people.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>

</x-layouts.app>
