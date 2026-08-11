<?php

declare(strict_types=1);

namespace App\Http\Requests\Health;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHealthEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date', 'before_or_equal:today'],
            'steps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
