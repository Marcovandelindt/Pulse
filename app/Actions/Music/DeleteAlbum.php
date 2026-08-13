<?php

declare(strict_types=1);

namespace App\Actions\Music;

use App\Models\Album;

final class DeleteAlbum
{
    public function handle(Album $album): void
    {
        $album->delete();
    }
}
