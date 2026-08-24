<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContactDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date'],
        ];
    }
}
