<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

final class MarkEpisodeWatchedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'watched_at' => ['nullable', 'date', 'before_or_equal:now'],
            'year_only' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'between:1,10'],
        ];
    }
}
