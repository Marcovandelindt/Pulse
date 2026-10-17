<?php

declare(strict_types=1);

namespace App\Http\Requests\Journal;

use App\Enums\Mood;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'         => ['required', 'date', 'unique:journal_entries,date'],
            'title'        => ['nullable', 'string', 'max:255'],
            'content'      => ['required', 'string'],
            'mood'         => ['nullable', new Enum(Mood::class)],
            'energy_level' => ['nullable', 'integer', 'between:1,5'],
            'tags'         => ['nullable', 'string', 'max:500'],
        ];
    }
}
