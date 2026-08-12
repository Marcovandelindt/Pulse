<?php

declare(strict_types=1);

namespace App\Console\Commands\Media;

use App\Models\TvSeries;
use App\Services\Tmdb\TMDBTVService;
use Illuminate\Console\Command;

final class BackfillTvEnglishNames extends Command
{
    protected $signature = 'media:backfill-tv-english-names';

    protected $description = 'Fetch English names for non-English TV series that are missing name_en';

    public function handle(TMDBTVService $service): int
    {
        $series = TvSeries::query()
            ->whereNull('name_en')
            ->whereNotIn('original_language', ['en', 'nl'])
            ->get();

        if ($series->isEmpty()) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $this->info("Backfilling English names for {$series->count()} series…");

        $bar = $this->output->createProgressBar($series->count());
        $bar->start();

        $updated = 0;

        foreach ($series as $show) {
            $englishName = $service->getEnglishName($show->tmdb_id);

            if ($englishName !== null && $englishName !== $show->name) {
                $show->update(['name_en' => $englishName]);
                $updated++;
            }

            $bar->advance();

            // Stay within TMDB rate limit (max 50 req/s)
            usleep(25_000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Updated {$updated} of {$series->count()} series.");

        return self::SUCCESS;
    }
}
