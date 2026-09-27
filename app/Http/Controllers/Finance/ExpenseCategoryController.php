<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();

        return view('pages.finance.categories', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100', 'unique:expense_categories,name'],
            'icon'  => ['required', 'string', 'max:10'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        ExpenseCategory::create($validated);

        return redirect()->route('finance.categories.index')->with('success', 'Category created.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100', 'unique:expense_categories,name,' . $expenseCategory->id],
            'icon'  => ['required', 'string', 'max:10'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $expenseCategory->update($validated);

        return redirect()->route('finance.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->delete();

        return redirect()->route('finance.categories.index')->with('success', 'Category deleted.');
    }
}
