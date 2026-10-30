<?php

declare(strict_types=1);

namespace App\Http\Requests\Gaming;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCodReviewRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'map' => ['required', 'string', 'max:100'],
            'mode' => ['required', 'string', 'max:100'],
            'result' => ['required', 'string', 'in:victory,defeat'],
            'team_score' => ['required', 'integer', 'min:0', 'max:9999'],
            'opponent_score' => ['required', 'integer', 'min:0', 'max:9999'],
            'score' => ['required', 'integer', 'min:0'],
            'kills' => ['required', 'integer', 'min:0', 'max:150'],
            'deaths' => ['required', 'integer', 'min:0', 'max:150'],
            'kd_ratio' => ['required', 'numeric', 'min:0'],
            'objective_score' => ['nullable', 'integer', 'min:0'],
            'damage' => ['required', 'integer', 'min:0'],
            'weapon_name' => ['nullable', 'string', 'max:100'],
            'weapon_category' => ['nullable', 'string', 'max:100'],
            'weapon_accuracy' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'weapon_kills' => ['nullable', 'integer', 'min:0', 'max:150'],
            'estimated_date' => ['required', 'date'],
        ];
    }
}
