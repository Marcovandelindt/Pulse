<x-layouts.app title="PlayStation Categories">

    <x-layout.page-header title="Categories">
        <x-slot:actions>
            <a href="{{ route('playstation.index') }}" class="btn btn--secondary btn--sm">← Back</a>
        </x-slot:actions>
    </x-layout.page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
             style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="New Category">
            <form method="POST" action="{{ route('playstation.categories.store') }}">
                @csrf
                <div class="flex gap-2">
                    <x-form.input
                        name="name"
                        placeholder="Category name"
                        :value="old('name')"
                        class="flex-1"
                    />
                    <button type="submit" class="btn btn--primary btn--sm">Add</button>
                </div>
                @error('name')
                    <p class="mt-1 text-xs" style="color: #f87171;">{{ $message }}</p>
                @enderror
            </form>
        </x-ui.card>

        <div class="lg:col-span-2">
            <x-ui.card title="All Categories">
                @if($categories->isEmpty())
                    <p class="text-sm" style="color: var(--color-text-muted)">No categories yet.</p>
                @else
                    <div class="divide-y" style="border-color: var(--color-border)">
                        @foreach($categories as $category)
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <span class="text-sm font-medium" style="color: var(--color-text-primary)">{{ $category->name }}</span>
                                    <span class="ml-2 text-xs" style="color: var(--color-text-muted)">{{ $category->games_count }} {{ Str::plural('game', $category->games_count) }}</span>
                                </div>
                                <form method="POST" action="{{ route('playstation.categories.destroy', $category) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm"
                                            onclick="return confirm('Delete this category?')">Delete</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

</x-layouts.app>
