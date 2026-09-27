<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\CreateExpense;
use App\Actions\Finance\ExpenseData;
use App\Actions\Finance\UpdateExpense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

final class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $month      = $request->integer('month', (int) now()->format('n'));
        $year       = $request->integer('year', (int) now()->format('Y'));
        $categoryId = $request->integer('category') ?: null;

        $period = Carbon::create($year, $month, 1);

        $query = Expense::with('category')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->when($categoryId, fn ($q) => $q->where('expense_category_id', $categoryId))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $expenses      = $query->get();
        $monthTotal    = $expenses->sum('amount');
        $categories    = ExpenseCategory::orderBy('name')->get();

        $byCategory = $expenses
            ->groupBy('expense_category_id')
            ->map(fn ($items) => [
                'category' => $items->first()->category,
                'total'    => $items->sum('amount'),
                'count'    => $items->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $monthlyTotals = Expense::selectRaw("strftime('%Y-%m', date) as month, SUM(amount) as total")
            ->where('date', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('pages.finance.index', compact(
            'expenses', 'monthTotal', 'categories', 'byCategory',
            'monthlyTotals', 'month', 'year', 'period', 'categoryId',
        ));
    }

    public function store(StoreExpenseRequest $request, CreateExpense $action): RedirectResponse
    {
        $action->handle(ExpenseData::fromRequest($request));

        return redirect()->route('finance.index')->with('success', 'Expense added.');
    }

    public function edit(Expense $expense): View
    {
        $categories = ExpenseCategory::orderBy('name')->get();

        return view('pages.finance.edit', compact('expense', 'categories'));
    }

    public function update(StoreExpenseRequest $request, Expense $expense, UpdateExpense $action): RedirectResponse
    {
        $action->handle($expense, ExpenseData::fromRequest($request));

        return redirect()->route('finance.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('finance.index')->with('success', 'Expense deleted.');
    }
}
