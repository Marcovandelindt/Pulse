<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'days'   => array_map('intval', (array) $this->input('days', [])),
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'days'        => ['required', 'array', 'min:1'],
            'days.*'      => ['integer', 'between:1,7'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i'],
            'valid_from'  => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'active'      => ['boolean'],
        ];
    }
}
