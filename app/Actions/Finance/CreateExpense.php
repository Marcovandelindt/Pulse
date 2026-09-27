<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Models\Expense;

final class CreateExpense
{
    public function handle(ExpenseData $data): Expense
    {
        return Expense::create([
            'amount'              => $data->amount,
            'description'        => $data->description,
            'date'               => $data->date,
            'expense_category_id' => $data->expenseCategoryId,
            'notes'              => $data->notes,
        ]);
    }
}
