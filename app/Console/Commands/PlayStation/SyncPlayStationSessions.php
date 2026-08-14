<?php

declare(strict_types=1);

namespace App\Console\Commands\PlayStation;

use App\Services\PlayStation\PlayStationScraperService;
use Illuminate\Console\Command;

final class SyncPlayStationSessions extends Command
{
    protected $signature = 'playstation:sync-sessions {username? : PSN username} {--cookie= : Session cookie} {--save-cookie : Save cookie to config} {--all : Sync all pages}';

    protected $description = 'Sync PlayStation sessions from PS-Timetracker';

    public function handle(PlayStationScraperService $scraper): void
    {
        $username = $this->argument('username') ?? config('services.playstation.username');
        $cookie = $this->option('cookie') ?? config('services.playstation.cookie');

        if ($cookie) {
            $scraper->setSessionCookie($cookie);
        }

        $synced = $scraper->syncSessions($username, (bool) $this->option('all'));

        $this->info("Synced {$synced} new sessions.");
    }
}
