<?php

declare(strict_types=1);

namespace App\Http\Requests\Insights;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateInsightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'content'      => ['required', 'string', 'max:10000'],
            'summary'      => ['nullable', 'string', 'max:500'],
            'category'     => ['nullable', 'string', 'max:100'],
            'tags'         => ['nullable', 'string', 'max:500'],
            'is_pinned'    => ['nullable', 'boolean'],
            'is_quick_ref' => ['nullable', 'boolean'],
        ];
    }
}
