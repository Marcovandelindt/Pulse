<x-layouts.app title="Expense Categories">

<div x-data="{ addOpen: false, editId: null, editName: '', editIcon: '', editColor: '' }">

<x-layout.page-header title="Expense categories">
    <x-slot:actions>
        <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add category</button>
        <a href="{{ route('finance.index') }}" class="btn btn--secondary btn--sm">← Back</a>
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

<x-ui.card>
    @if($categories->isEmpty())
        <x-ui.empty-state title="No categories yet" description="Add your first category.">
            <x-slot:action>
                <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add category</button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="finance-category-manage-list">
            @foreach($categories as $category)
                <div class="finance-category-manage-item">
                    <span class="finance-category-manage-item__icon"
                          style="background: {{ $category->color }}20; color: {{ $category->color }};">
                        {{ $category->icon }}
                    </span>
                    <span class="finance-category-manage-item__name">{{ $category->name }}</span>
                    <span class="finance-category-manage-item__count">{{ $category->expenses_count }} expenses</span>
                    <div class="finance-category-manage-item__actions">
                        <button
                            @click="editId = {{ $category->id }}; editName = '{{ addslashes($category->name) }}'; editIcon = '{{ $category->icon }}'; editColor = '{{ $category->color }}'"
                            class="btn btn--secondary btn--sm"
                        >Edit</button>
                        <form method="POST" action="{{ route('finance.categories.destroy', $category) }}"
                              onsubmit="return confirm('Delete {{ addslashes($category->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-ui.card>

{{-- Add modal --}}
<div x-show="addOpen" x-transition.opacity class="modal" style="display:none;" @keydown.escape.window="addOpen = false">
    <div class="modal__backdrop" @click="addOpen = false"></div>
    <div class="modal__panel" @click.stop>
        <div class="modal__header">
            <h3 class="modal__title">Add category</h3>
            <button @click="addOpen = false" class="modal__close">✕</button>
        </div>
        <form method="POST" action="{{ route('finance.categories.store') }}" class="modal__body">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Icon (emoji)</label>
                    <input type="text" name="icon" class="form-input" required value="💳" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <input type="color" name="color" class="form-input" required value="#6366f1" style="height: 2.5rem; padding: 0.25rem;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-input" required maxlength="100" autofocus>
            </div>
            <div class="modal__footer">
                <button type="button" @click="addOpen = false" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Add</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit modal --}}
<div x-show="editId !== null" x-transition.opacity class="modal" style="display:none;" @keydown.escape.window="editId = null">
    <div class="modal__backdrop" @click="editId = null"></div>
    <template x-for="cat in {{ $categories->toJson() }}" :key="cat.id">
        <div x-show="editId === cat.id" class="modal__panel" @click.stop>
            <div class="modal__header">
                <h3 class="modal__title">Edit category</h3>
                <button @click="editId = null" class="modal__close">✕</button>
            </div>
            <form :action="`/finance/categories/${cat.id}`" method="POST" class="modal__body">
                @csrf @method('PATCH')
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Icon (emoji)</label>
                        <input type="text" name="icon" class="form-input" required maxlength="10" :value="editIcon">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-input" required style="height: 2.5rem; padding: 0.25rem;" :value="editColor">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-input" required maxlength="100" :value="editName">
                </div>
                <div class="modal__footer">
                    <button type="button" @click="editId = null" class="btn btn--secondary">Cancel</button>
                    <button type="submit" class="btn btn--primary">Save</button>
                </div>
            </form>
        </div>
    </template>
</div>

</div>
</x-layouts.app>
