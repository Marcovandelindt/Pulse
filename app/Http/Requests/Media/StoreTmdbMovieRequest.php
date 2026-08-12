<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTmdbMovieRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tmdb_id' => ['required', 'integer'],
        ];
    }
}
