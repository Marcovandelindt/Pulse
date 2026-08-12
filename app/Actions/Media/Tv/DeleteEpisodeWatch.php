<?php

declare(strict_types=1);

namespace App\Actions\Media\Tv;

use App\Models\EpisodeWatch;

final class DeleteEpisodeWatch
{
    public function handle(EpisodeWatch $watch): void
    {
        $episode = $watch->episode()->with('season.series')->first();
        $watch->delete();

        $episode->season->updateProgress();
        $episode->season->series->updateProgress();
    }
}
