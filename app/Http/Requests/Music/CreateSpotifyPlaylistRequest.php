<?php

declare(strict_types=1);

namespace App\Http\Requests\Music;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateSpotifyPlaylistRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'period_type'   => ['required', Rule::in(['last_30_days', 'last_6_months', 'this_year', 'specific_year', 'custom'])],
            'year'          => ['required_if:period_type,specific_year', 'nullable', 'integer', 'min:2000', 'max:'.now()->year],
            'from'          => ['required_if:period_type,custom', 'nullable', 'date', 'before_or_equal:to'],
            'to'            => ['required_if:period_type,custom', 'nullable', 'date', 'before_or_equal:today'],
            'track_limit'   => ['required', 'integer', 'min:5', 'max:100'],
            'playlist_name' => ['required', 'string', 'max:100'],
        ];
    }
}
