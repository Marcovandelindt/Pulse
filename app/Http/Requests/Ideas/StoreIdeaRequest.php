<?php

declare(strict_types=1);

namespace App\Http\Requests\Ideas;

use App\Enums\IdeaPriority;
use App\Enums\IdeaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreIdeaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'module'      => ['nullable', 'string', 'max:50'],
            'priority'    => ['required', new Enum(IdeaPriority::class)],
            'status'      => ['required', new Enum(IdeaStatus::class)],
        ];
    }
}
