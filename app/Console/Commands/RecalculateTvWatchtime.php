<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TvSeries;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tv:recalculate-watchtime')]
#[Description('Recalculate watched_runtime_minutes for all TV series based on actual watch records')]
final class RecalculateTvWatchtime extends Command
{
    public function handle(): void
    {
        $series = TvSeries::with('seasons')->get();

        $this->withProgressBar($series, function (TvSeries $show) {
            $show->load('seasons.episodes');
            $show->updateProgress();
        });

        $this->newLine();
        $this->info("Recalculated watchtime for {$series->count()} series.");
    }
}
