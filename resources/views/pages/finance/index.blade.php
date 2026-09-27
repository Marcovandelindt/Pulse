<x-layouts.app title="Finance">

<div x-data="{ addOpen: false }" @keydown.escape.window="addOpen = false">

<x-layout.page-header title="Finance">
    <x-slot:actions>
        <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add expense</button>
        <a href="{{ route('finance.categories.index') }}" class="btn btn--secondary btn--sm">Categories</a>
    </x-slot:actions>
</x-layout.page-header>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
         style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
        {{ session('success') }}
    </div>
@endif

{{-- Month navigation --}}
<div class="flex items-center gap-3 mb-6">
    @php
        $prev = $period->copy()->subMonth();
        $next = $period->copy()->addMonth();
    @endphp
    <a href="{{ route('finance.index', ['month' => $prev->month, 'year' => $prev->year]) }}" class="btn btn--secondary btn--sm">←</a>
    <span class="text-base font-semibold" style="color: var(--color-text-primary); min-width: 130px; text-align: center;">
        {{ $period->format('F Y') }}
    </span>
    @if($next->lte(now()))
        <a href="{{ route('finance.index', ['month' => $next->month, 'year' => $next->year]) }}" class="btn btn--secondary btn--sm">→</a>
    @else
        <span class="btn btn--secondary btn--sm" style="opacity: 0.3; pointer-events: none;">→</span>
    @endif
</div>

{{-- Stats row --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
    <x-stats.stat-card
        label="Total this month"
        :value="'€ ' . number_format((float) $monthTotal, 2, ',', '.')"
        icon="credit-card"
    />
    <x-stats.stat-card
        label="Expenses"
        :value="$expenses->count()"
        icon="play"
    />
    <x-stats.stat-card
        label="Avg per day"
        :value="$period->daysInMonth > 0 ? '€ ' . number_format((float) $monthTotal / $period->daysInMonth, 2, ',', '.') : '—'"
        icon="clock"
    />
    <x-stats.stat-card
        label="Categories"
        :value="$byCategory->count()"
        icon="heart"
    />
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">

    {{-- Monthly chart --}}
    @if($monthlyTotals->isNotEmpty())
        <div class="lg:col-span-2">
            <x-ui.card title="Monthly spending">
                <canvas
                    data-chart="bar"
                    data-chart-data="{{ json_encode([
                        'labels' => $monthlyTotals->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y')),
                        'values' => $monthlyTotals->pluck('total')->map(fn($v) => round((float) $v, 2)),
                    ]) }}"
                    style="max-height: 220px;"
                ></canvas>
            </x-ui.card>
        </div>
    @endif

    {{-- Category breakdown --}}
    @if($byCategory->isNotEmpty())
        <x-ui.card title="By category">
            <div class="finance-category-list">
                @foreach($byCategory as $row)
                    <div class="finance-category-item">
                        <span class="finance-category-item__icon">{{ $row['category']?->icon ?? '📦' }}</span>
                        <div class="finance-category-item__info">
                            <span class="finance-category-item__name">{{ $row['category']?->name ?? 'Uncategorised' }}</span>
                            <div class="finance-category-item__bar-wrap">
                                <div class="finance-category-item__bar"
                                     style="width: {{ $monthTotal > 0 ? round($row['total'] / $monthTotal * 100) : 0 }}%;
                                            background: {{ $row['category']?->color ?? '#6b7280' }};"></div>
                            </div>
                        </div>
                        <span class="finance-category-item__amount">€ {{ number_format((float) $row['total'], 2, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

</div>

{{-- Expense list --}}
<x-ui.card>
    <x-slot:title>
        Expenses
        <span style="color: var(--color-text-muted); font-weight: 400; font-size: 0.8125rem; margin-left: 0.25rem;">{{ $expenses->count() }}</span>
    </x-slot:title>

    @if($expenses->isEmpty())
        <x-ui.empty-state title="No expenses yet" description="Add your first expense for this month.">
            <x-slot:action>
                <button @click="addOpen = true" class="btn btn--primary btn--sm">+ Add expense</button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="finance-expense-list">
            @foreach($expenses as $expense)
                <div class="finance-expense-item">
                    <span class="finance-expense-item__icon">{{ $expense->category?->icon ?? '📦' }}</span>
                    <div class="finance-expense-item__info">
                        <span class="finance-expense-item__desc">{{ $expense->description }}</span>
                        <span class="finance-expense-item__meta">
                            {{ $expense->date->format('d M') }}
                            @if($expense->category)
                                · {{ $expense->category->name }}
                            @endif
                            @if($expense->notes)
                                · <span style="font-style: italic;">{{ $expense->notes }}</span>
                            @endif
                        </span>
                    </div>
                    <span class="finance-expense-item__amount">€ {{ number_format((float) $expense->amount, 2, ',', '.') }}</span>
                    <div class="finance-expense-item__actions">
                        <a href="{{ route('finance.edit', $expense) }}" class="btn btn--secondary btn--sm">Edit</a>
                        <form method="POST" action="{{ route('finance.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--sm">✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-ui.card>

{{-- Add expense modal --}}
<div x-show="addOpen" x-transition.opacity class="modal" style="display:none;">
    <div class="modal__backdrop" @click="addOpen = false"></div>
    <div class="modal__panel" @click.stop>
        <div class="modal__header">
            <h3 class="modal__title">Add expense</h3>
            <button @click="addOpen = false" class="modal__close">✕</button>
        </div>
        <form method="POST" action="{{ route('finance.store') }}" class="modal__body">
            @csrf
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-input" required autofocus
                       value="{{ old('description') }}">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Amount (€)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="form-input" required
                           value="{{ old('amount') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-input" required
                           value="{{ old('date', today()->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="expense_category_id" class="form-input">
                    <option value="">— None —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->icon }} {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes (optional)</label>
                <textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
            </div>
            <div class="modal__footer">
                <button type="button" @click="addOpen = false" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Save</button>
            </div>
        </form>
    </div>
</div>

</div>
</x-layouts.app>
