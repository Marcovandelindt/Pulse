<?php

declare(strict_types=1);

namespace App\Http\Requests\Gaming;

use Illuminate\Foundation\Http\FormRequest;

final class StorePlayStationTodoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
