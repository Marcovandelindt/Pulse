<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Models\Expense;

final class UpdateExpense
{
    public function handle(Expense $expense, ExpenseData $data): Expense
    {
        $expense->update([
            'amount'              => $data->amount,
            'description'        => $data->description,
            'date'               => $data->date,
            'expense_category_id' => $data->expenseCategoryId,
            'notes'              => $data->notes,
        ]);

        return $expense;
    }
}
