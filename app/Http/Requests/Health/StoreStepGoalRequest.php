<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStepGoalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'steps' => ['required', 'integer', 'min:1', 'max:100000'],
            'effective_from' => ['required', 'date', 'before_or_equal:today', 'unique:step_goals,effective_from'],
        ];
    }
}
