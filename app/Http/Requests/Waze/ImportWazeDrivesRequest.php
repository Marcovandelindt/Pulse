<?php

declare(strict_types=1);

namespace App\Http\Requests\Waze;

use Illuminate\Foundation\Http\FormRequest;

final class ImportWazeDrivesRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:zip,csv', 'max:51200'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Select a Waze data archive (.zip or .csv).',
            'file.mimes' => 'Only .zip and .csv files are accepted.',
            'file.max' => 'The file may not be larger than 50 MB.',
        ];
    }
}
