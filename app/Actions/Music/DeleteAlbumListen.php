<?php

declare(strict_types=1);

namespace App\Actions\Music;

use App\Models\AlbumListen;

final class DeleteAlbumListen
{
    public function handle(AlbumListen $listen): void
    {
        $album = $listen->album;
        $listen->delete();

        // Recalculate listen count from DB
        $album->listen_count = $album->listens()->count();
        $album->last_listened_at = $album->listens()
            ->whereNotNull('listened_at')
            ->orderByDesc('listened_at')
            ->value('listened_at');
        $album->save();
    }
}
