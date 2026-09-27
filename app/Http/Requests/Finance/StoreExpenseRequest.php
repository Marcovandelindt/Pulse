<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class StoreExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'              => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'description'        => ['required', 'string', 'max:255'],
            'date'               => ['required', 'date', 'before_or_equal:today'],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }
}
