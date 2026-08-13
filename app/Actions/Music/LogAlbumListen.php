<?php

declare(strict_types=1);

namespace App\Actions\Music;

use App\Data\AlbumListenData;
use App\Models\Album;
use App\Models\AlbumListen;

final class LogAlbumListen
{
    public function handle(Album $album, AlbumListenData $data): AlbumListen
    {
        $listen = $album->listens()->create([
            'listened_at' => $data->listenedAt,
            'year_only' => $data->yearOnly,
            'notes' => $data->notes,
            'rating' => $data->rating,
        ]);

        $album->incrementListenCount();

        return $listen;
    }
}
