<?php

declare(strict_types=1);

namespace App\Actions\Nintendo;

use App\Models\NintendoGame;
use Illuminate\Support\Str;

final class CreateNintendoGame
{
    public function handle(array $data): NintendoGame
    {
        return NintendoGame::create([
            'application_id' => 'manual-' . Str::uuid(),
            'igdb_id'        => $data['igdb_id'] ?: null,
            'name'           => $data['name'],
            'image_url'      => $data['cover_url'] ?: null,
            'genres'         => $data['genres'] ?? null,
            'released_at'    => $data['released_at'] ?: null,
            'total_minutes'  => 0,
        ]);
    }
}
