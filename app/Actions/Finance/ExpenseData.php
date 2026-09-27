<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Http\Requests\Finance\StoreExpenseRequest;
use Illuminate\Support\Carbon;

final readonly class ExpenseData
{
    public function __construct(
        public float $amount,
        public string $description,
        public Carbon $date,
        public ?int $expenseCategoryId,
        public ?string $notes,
    ) {}

    public static function fromRequest(StoreExpenseRequest $request): self
    {
        return new self(
            amount: (float) $request->validated('amount'),
            description: $request->validated('description'),
            date: Carbon::parse($request->validated('date')),
            expenseCategoryId: $request->validated('expense_category_id') ? (int) $request->validated('expense_category_id') : null,
            notes: $request->validated('notes'),
        );
    }
}
