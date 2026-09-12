<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContactGiftIdeaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'idea'  => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
