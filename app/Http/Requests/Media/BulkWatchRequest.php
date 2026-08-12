<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

final class BulkWatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'watched_at' => ['nullable', 'date', 'before_or_equal:now'],
            'year_only' => ['boolean'],
        ];
    }
}
