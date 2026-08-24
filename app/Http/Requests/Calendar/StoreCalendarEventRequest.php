<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\EventType;
use App\Enums\RecurrenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $date = $this->input('starts_at_date');
        $time = $this->boolean('all_day') ? '00:00' : ($this->input('starts_at_time') ?: '00:00');
        $startsAt = $date.' '.$time.':00';

        $endsAt = null;
        if ($this->filled('ends_at_date')) {
            $endTime = $this->boolean('all_day') ? '23:59' : ($this->input('ends_at_time') ?: '23:59');
            $endsAt = $this->input('ends_at_date').' '.$endTime.':00';
        }

        $this->merge([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => $this->boolean('all_day'),
            'recurrence' => $this->input('recurrence', RecurrenceType::None->value),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['boolean'],
            'type' => ['required', 'string', new Enum(EventType::class)],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'recurrence' => ['required', 'string', new Enum(RecurrenceType::class)],
            'recurrence_ends_at' => ['nullable', 'date'],
        ];
    }
}
