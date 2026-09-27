<?php

declare(strict_types=1);

namespace App\Http\Requests\Gaming;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateGameNotesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
