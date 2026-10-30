<?php

declare(strict_types=1);

namespace App\Http\Requests\Gaming;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCodScreenshotRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'screenshots' => ['required', 'array', 'min:1', 'max:10'],
            'screenshots.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'screenshots.required' => 'Upload at least one screenshot.',
            'screenshots.max' => 'You can upload a maximum of 10 screenshots at once.',
            'screenshots.*.image' => 'Each file must be an image.',
            'screenshots.*.max' => 'Each screenshot may not exceed 10 MB.',
        ];
    }
}
