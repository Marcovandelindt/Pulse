<?php

declare(strict_types=1);

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOverlandBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // token auth handled by overland.token middleware
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'locations' => ['required', 'array', 'min:1'],
            'locations.*.geometry.coordinates' => ['required', 'array', 'size:2'],
            'locations.*.geometry.coordinates.0' => ['required', 'numeric'],
            'locations.*.geometry.coordinates.1' => ['required', 'numeric'],
            'locations.*.properties.timestamp' => ['required', 'string'],
        ];
    }
}
