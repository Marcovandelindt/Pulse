<?php

declare(strict_types=1);

namespace App\Http\Requests\Music;

use Illuminate\Foundation\Http\FormRequest;

final class LogAlbumListenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listened_at' => ['nullable', 'date', 'before_or_equal:now'],
            'year_only' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'between:1,10'],
        ];
    }
}
