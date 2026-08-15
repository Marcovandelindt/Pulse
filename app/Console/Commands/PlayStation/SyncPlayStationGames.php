<?php

declare(strict_types=1);

namespace App\Console\Commands\PlayStation;

use App\Services\PlayStation\PlayStationScraperService;
use Illuminate\Console\Command;

final class SyncPlayStationGames extends Command
{
    protected $signature = 'playstation:sync {username? : PSN username} {--cookie= : Session cookie}';

    protected $description = 'Sync PlayStation games from PS-Timetracker';

    public function handle(PlayStationScraperService $scraper): void
    {
        $username = $this->argument('username') ?? config('services.playstation.username');
        $cookie = $this->option('cookie');

        if (! $username) {
            $this->error('No username provided. Set PLAYSTATION_USERNAME in .env or pass it as argument.');

            return;
        }

        if ($cookie) {
            $scraper->setSessionCookie($cookie);
        }

        $synced = $scraper->syncGames($username);
        $this->info("Synced {$synced} games.");
    }
}
