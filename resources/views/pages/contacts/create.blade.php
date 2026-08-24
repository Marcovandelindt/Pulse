<x-layouts.app title="Add Person">

    <x-layout.page-header title="Add Person">
        <x-slot:actions>
            <a href="{{ route('people.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('people.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="flex flex-col gap-5">

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

            <div class="form-group" x-data="{ yearUnknown: {{ old('birth_year_unknown') ? 'true' : 'false' }} }">
                <label class="form-label">Birthday</label>

                <input
                    type="date"
                    id="birthdate"
                    name="birthdate"
                    value="{{ old('birthdate') }}"
                    class="form-input"
                    :disabled="yearUnknown"
                    x-show="!yearUnknown"
                >

                <div class="flex gap-2" x-show="yearUnknown">
                    <select name="birth_month" class="form-select" :disabled="!yearUnknown">
                        <option value="">Month</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" {{ old('birth_month') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="birth_day" class="form-select" :disabled="!yearUnknown">
                        <option value="">Day</option>
                        @foreach (range(1, 31) as $d)
                            <option value="{{ $d }}" {{ old('birth_day') == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-2 mt-1" style="cursor: pointer;">
                    <input type="checkbox" name="birth_year_unknown" value="1"
                           x-model="yearUnknown" class="form-checkbox">
                    <span style="font-size: 0.8125rem; color: var(--color-text-muted);">Birth year unknown</span>
                </label>

                <x-form.error name="birthdate" />
                <x-form.error name="birth_month" />
                <x-form.error name="birth_day" />
            </div>

            <div class="form-group">
                <label class="form-label" for="death_date">Date of death <span class="form-label__optional">optional</span></label>
                <input
                    type="date"
                    id="death_date"
                    name="death_date"
                    value="{{ old('death_date') }}"
                    class="form-input"
                >
                <x-form.error name="death_date" />
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

            </div>{{-- /gap wrapper --}}

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Add person</button>
                <a href="{{ route('people.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>

</x-layouts.app>
