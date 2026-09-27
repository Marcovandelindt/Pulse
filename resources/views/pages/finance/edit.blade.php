<x-layouts.app title="Edit Expense">

<x-layout.page-header title="Edit expense">
    <x-slot:actions>
        <a href="{{ route('finance.index') }}" class="btn btn--secondary btn--sm">← Back</a>
    </x-slot:actions>
</x-layout.page-header>

<div style="max-width: 480px;">
    <x-ui.card>
        <form method="POST" action="{{ route('finance.update', $expense) }}">
            @csrf @method('PATCH')
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-input" required
                       value="{{ old('description', $expense->description) }}">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Amount (€)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="form-input" required
                           value="{{ old('amount', $expense->amount) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-input" required
                           value="{{ old('date', $expense->date->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="expense_category_id" class="form-input">
                    <option value="">— None —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ old('expense_category_id', $expense->expense_category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->icon }} {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes (optional)</label>
                <textarea name="notes" rows="3" class="form-input">{{ old('notes', $expense->notes) }}</textarea>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <a href="{{ route('finance.index') }}" class="btn btn--secondary">Cancel</a>
                <button type="submit" class="btn btn--primary">Save changes</button>
            </div>
        </form>
    </x-ui.card>
</div>

</x-layouts.app>
