<?php

declare(strict_types=1);

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOwnTracksLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            '_type' => ['required', 'string'],
            'lat'   => ['nullable', 'numeric'],
            'lon'   => ['nullable', 'numeric'],
            'tst'   => ['nullable', 'integer'],
            'alt'   => ['nullable', 'numeric'],
            'acc'   => ['nullable', 'numeric'],
            'vel'   => ['nullable', 'numeric'],
            'batt'  => ['nullable', 'integer'],
            'tid'   => ['nullable', 'string'],
            't'     => ['nullable', 'string'],
            'conn'  => ['nullable', 'string'],
            'bs'    => ['nullable', 'integer'],
            'vac'   => ['nullable', 'numeric'],
        ];
    }
}
