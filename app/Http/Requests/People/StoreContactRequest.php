<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'death_date' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:birthdate'],
            'relationship_type_id' => ['nullable', 'exists:relationship_types,id'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
