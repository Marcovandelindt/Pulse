<?php

declare(strict_types=1);

namespace App\Http\Requests\Jobs;

use Illuminate\Foundation\Http\FormRequest;

final class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'employer' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['required', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'work_start_time' => ['nullable', 'date_format:H:i'],
            'work_end_time' => ['nullable', 'date_format:H:i', 'after:work_start_time'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
