<?php

declare(strict_types=1);

namespace App\Http\Requests\Gaming;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWishlistItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:PS3,PS4,PS5,PSVITA'],
            'price'    => ['nullable', 'numeric', 'min:0'],
            'psn_url'  => ['nullable', 'url', 'max:500'],
            'notes'    => ['nullable', 'string', 'max:2000'],
        ];
    }
}
