<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\EpisodeWatch;
use App\Models\TvEpisode;

final class MarkEpisodeWatched
{
    public function handle(TvEpisode $episode, EpisodeWatchData $data): EpisodeWatch
    {
        return $episode->addWatch(
            watchedAt: $data->watchedAt,
            yearOnly: $data->yearOnly,
            notes: $data->notes,
            rating: $data->rating,
        );
    }
}
