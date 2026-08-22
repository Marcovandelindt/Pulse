<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\EpisodeWatch;
use App\Models\TvSeries;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class FixTvWatchedDates extends Command
{
    protected $signature = 'tv:fix-watched-dates';

    protected $description = 'Recalculate last_watched_at and first_watched_at for all series from actual episode watch dates';

    public function handle(): int
    {
        $series = TvSeries::has('seasons')->get();

        $this->info("Fixing watched dates for {$series->count()} series...");

        $updated = 0;

        foreach ($series as $show) {
            $seasonIds = $show->seasons()->select('id');

            $latest = EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
                ->whereIn('tv_episodes.tv_season_id', $seasonIds)
                ->whereNotNull('episode_watches.watched_at')
                ->max('episode_watches.watched_at');

            $earliest = EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
                ->whereIn('tv_episodes.tv_season_id', $seasonIds)
                ->whereNotNull('episode_watches.watched_at')
                ->min('episode_watches.watched_at');

            if ($latest === null) {
                continue;
            }

            $show->last_watched_at  = Carbon::parse($latest);
            $show->first_watched_at = $earliest ? Carbon::parse($earliest) : $show->first_watched_at;
            $show->saveQuietly();

            $updated++;
        }

        $this->info("Updated {$updated} series.");

        return Command::SUCCESS;
    }
}
