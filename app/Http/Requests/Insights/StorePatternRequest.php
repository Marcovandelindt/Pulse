<?php

declare(strict_types=1);

namespace App\Http\Requests\Insights;

use Illuminate\Foundation\Http\FormRequest;

final class StorePatternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
