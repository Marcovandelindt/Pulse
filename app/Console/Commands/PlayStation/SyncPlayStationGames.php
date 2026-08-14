<?php

declare(strict_types=1);

namespace App\Console\Commands\PlayStation;

use App\Services\PlayStation\PlayStationScraperService;
use Illuminate\Console\Command;

final class SyncPlayStationGames extends Command
{
    protected $signature = 'playstation:sync {username? : PSN username}';

    protected $description = 'Sync PlayStation games from PS-Timetracker';

    public function handle(PlayStationScraperService $scraper): void
    {
        $username = $this->argument('username') ?? config('services.playstation.username');
        $synced = $scraper->syncGames($username);

        $this->info("Synced {$synced} games.");
    }
}
