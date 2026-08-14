<x-layouts.app title="Add Game — PlayStation">

    <x-layout.page-header title="Add Game">
        <x-slot:actions>
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <form method="POST" action="{{ route('playstation.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Game Name <span style="color: #ef4444">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-input"
                    placeholder="e.g. God of War Ragnarök"
                    required
                >
                @error('name')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="platform">Platform <span style="color: #ef4444">*</span></label>
                <select id="platform" name="platform" class="form-input" required>
                    <option value="">— Select Platform —</option>
                    @foreach(['PS5', 'PS4', 'PS3', 'PSVITA'] as $p)
                        <option value="{{ $p }}" {{ old('platform') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
                @error('platform')
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
                    value="{{ old('price') }}"
                    class="form-input"
                    placeholder="e.g. 59.99"
                >
                @error('price')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="image">Cover Image</label>
                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/*"
                    class="form-input"
                >
                @error('image')
                    <x-form.error>{{ $message }}</x-form.error>
                @enderror
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn--primary">Add Game</button>
                <a href="{{ route('playstation.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>

</x-layouts.app>
