<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('birth_year_unknown') && $this->filled('birth_month') && $this->filled('birth_day')) {
            $month = str_pad((string) (int) $this->input('birth_month'), 2, '0', STR_PAD_LEFT);
            $day = str_pad((string) (int) $this->input('birth_day'), 2, '0', STR_PAD_LEFT);
            $this->merge(['birthdate' => "1900-{$month}-{$day}"]);
        }
    }

    public function rules(): array
    {
        $yearUnknown = $this->boolean('birth_year_unknown');

        return [
            'name' => ['required', 'string', 'max:100'],
            'birthdate' => $yearUnknown
                                        ? ['nullable', 'date']
                                        : ['nullable', 'date', 'before_or_equal:today'],
            'birth_year_unknown' => ['boolean'],
            'birth_month' => $yearUnknown ? ['required', 'integer', 'between:1,12'] : ['nullable'],
            'birth_day' => $yearUnknown ? ['required', 'integer', 'between:1,31'] : ['nullable'],
            'death_date' => array_values(array_filter([
                'nullable', 'date', 'before_or_equal:today',
                $yearUnknown ? null : 'after_or_equal:birthdate',
            ])),
            'relationship_type_id' => ['nullable', 'exists:relationship_types,id'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
